"""
TG Forwarder Pro — Backend API
================================
Install:
    pip install telethon flask flask-cors --break-system-packages

Jalankan:
    python3 app.py
"""

import asyncio
import threading
import logging
import json
import os
import time
from datetime import datetime
from collections import deque, defaultdict
from functools import wraps

from flask import Flask, request, jsonify, send_from_directory
from flask_cors import CORS
from telethon import TelegramClient, events
from telethon.tl.functions.account import UpdateProfileRequest
from telethon.tl.functions.photos import UploadProfilePhotoRequest
import base64

# ─────────────────────────────────────────────────────────
#  KONFIGURASI
# ─────────────────────────────────────────────────────────

CONFIG_FILE  = "config.json"
SESSION_FILE = "userbot_session"

DASHBOARD_USER     = "admin"
DASHBOARD_PASSWORD = "password123"   # ← GANTI!
SECRET_KEY         = "ganti_ini_random_panjang"  # ← GANTI!

DEFAULT_CONFIG = {
    "api_id"      : "",
    "api_hash"    : "",
    "rules"       : [],
    "global_filter": {
        "text": True, "photo": True, "video": True,
        "document": True, "audio": True, "sticker": False
    }
}

# ─────────────────────────────────────────────────────────
#  CONFIG
# ─────────────────────────────────────────────────────────

def load_config():
    if os.path.exists(CONFIG_FILE):
        with open(CONFIG_FILE, "r", encoding="utf-8") as f:
            return json.load(f)
    return dict(DEFAULT_CONFIG)

def save_config(cfg):
    with open(CONFIG_FILE, "w", encoding="utf-8") as f:
        json.dump(cfg, f, indent=2, ensure_ascii=False)

config = load_config()

# ─────────────────────────────────────────────────────────
#  STATE
# ─────────────────────────────────────────────────────────

class BotState:
    def __init__(self):
        self.running      = False
        self.connected    = False
        self.start_time   = None
        self.me           = None
        self.logs         = deque(maxlen=300)
        self.errors       = deque(maxlen=100)
        self.stats        = defaultdict(int)
        self.lock         = threading.Lock()
        self.resolved     = []
        self.bulk_jobs    = {}  # key → {status, total, done, paused, stopped, ...}

    def log(self, level, msg):
        entry = {"time": datetime.now().strftime("%H:%M:%S"), "level": level, "msg": msg}
        with self.lock:
            self.logs.appendleft(entry)
            if level == "ERROR":
                self.errors.appendleft(entry)

    def get_logs(self, n=150):
        with self.lock:
            return list(self.logs)[:n]

    def get_errors(self):
        with self.lock:
            return list(self.errors)

state = BotState()

# ─────────────────────────────────────────────────────────
#  LOGGING BRIDGE
# ─────────────────────────────────────────────────────────

class StateLogHandler(logging.Handler):
    def emit(self, record):
        state.log(record.levelname, self.format(record))

root_log = logging.getLogger()
root_log.setLevel(logging.INFO)
root_log.addHandler(StateLogHandler())
root_log.addHandler(logging.FileHandler("forwarder.log", encoding="utf-8"))
log = logging.getLogger("TGForwarder")

# ─────────────────────────────────────────────────────────
#  USERBOT
# ─────────────────────────────────────────────────────────

client     = None
bot_thread = None
bot_loop   = None
last_sent  = defaultdict(float)

def is_allowed(message, filt):
    if message.sticker:   return filt.get("sticker", False)
    if message.photo:     return filt.get("photo", True)
    if message.video or message.video_note: return filt.get("video", True)
    if message.document:  return filt.get("document", True)
    if message.audio or message.voice: return filt.get("audio", True)
    if message.text:      return filt.get("text", True)
    return True

def should_send(rule_idx, tgt_idx, interval_min):
    key = f"rule_{rule_idx}_target_{tgt_idx}"
    now = time.time()
    if now - last_sent[key] >= interval_min * 60:
        last_sent[key] = now
        return True
    return False

async def resolve_rules():
    resolved = []
    for i, rule in enumerate(config.get("rules", [])):
        if not rule.get("enabled", True):
            resolved.append({"index": i, "enabled": False,
                             "source_title": rule.get("source","?"), "targets": []})
            continue
        try:
            src = await client.get_entity(rule["source"])
            targets = []
            for j, tgt in enumerate(rule.get("targets", [])):
                try:
                    te = await client.get_entity(tgt["username"])
                    targets.append({
                        "index"       : j,
                        "username"    : tgt["username"],
                        "title"       : getattr(te, "title", tgt["username"]),
                        "entity"      : te,
                        "interval_min": tgt.get("interval_min", 0),
                        "delay_sec"   : tgt.get("delay_sec", 0),
                        "enabled"     : tgt.get("enabled", True),
                    })
                    log.info(f"✅ Target: {tgt['username']}")
                except Exception as e:
                    log.error(f"❌ Target {tgt['username']}: {e}")
            resolved.append({
                "index"        : i,
                "enabled"      : True,
                "source"       : rule["source"],
                "source_id"    : src.id,
                "source_entity": src,
                "source_title" : getattr(src, "title", rule["source"]),
                "targets"      : targets,
                "filter"       : rule.get("filter", config.get("global_filter", {})),
            })
            log.info(f"📡 Rule {i+1} [{src.title}] → {len(targets)} target")
        except Exception as e:
            log.error(f"❌ Rule {i+1} source gagal: {e}")
    with state.lock:
        state.resolved = resolved

async def run_bot():
    global client
    client = TelegramClient(SESSION_FILE, int(config["api_id"]), config["api_hash"])
    try:
        await client.start()
        state.connected = True
        me = await client.get_me()
        state.me = {
            "id"      : me.id,
            "name"    : f"{me.first_name or ''} {me.last_name or ''}".strip(),
            "username": me.username or "",
            "phone"   : me.phone or "",
        }
        log.info(f"✅ Login: {state.me['name']} (@{state.me['username']})")
    except Exception as e:
        log.error(f"❌ Login gagal: {e}")
        state.running = False
        return

    await resolve_rules()

    @client.on(events.NewMessage())
    async def handler(event):
        for rule in state.resolved:
            if not rule.get("enabled"): continue
            if event.chat_id != rule.get("source_id"): continue
            filt = rule.get("filter", {})
            if not is_allowed(event.message, filt): continue
            for tgt in rule.get("targets", []):
                if not tgt.get("enabled"): continue
                interval = tgt.get("interval_min", 0)
                if interval > 0 and not should_send(rule["index"], tgt["index"], interval):
                    continue
                delay = tgt.get("delay_sec", 0)
                if delay > 0:
                    await asyncio.sleep(delay)
                try:
                    await client.forward_messages(
                        entity=tgt["entity"],
                        messages=event.message,
                        from_peer=rule["source_id"],
                    )
                    key = f"rule_{rule['index']}_target_{tgt['index']}"
                    with state.lock:
                        state.stats[key] += 1
                    log.info(f"📨 [{rule['source_title']}] → [{tgt['title']}] msg={event.message.id}")
                except Exception as e:
                    log.error(f"❌ Forward gagal [{tgt['username']}]: {e}")

    log.info("👂 Bot aktif, menunggu pesan baru...")
    await client.run_until_disconnected()
    state.connected = False
    state.running   = False
    log.info("🔴 Bot dihentikan.")

def _bot_thread_fn():
    global bot_loop
    bot_loop = asyncio.new_event_loop()
    asyncio.set_event_loop(bot_loop)
    bot_loop.run_until_complete(run_bot())

def stop_bot_now():
    global client, bot_loop
    if client and bot_loop:
        asyncio.run_coroutine_threadsafe(client.disconnect(), bot_loop)
    state.running   = False
    state.connected = False

# ─────────────────────────────────────────────────────────
#  BULK FORWARD TASK (parallel per grup)
# ─────────────────────────────────────────────────────────

async def bulk_forward_task(rule_idx, tgt_idx, source_entity,
                             target_entity, start_msg_id, interval_sec, job_key):
    log.info(f"🚀 Bulk start [{job_key}] dari msg_id={start_msg_id} jeda={interval_sec}s")
    try:
        messages = []
        async for msg in client.iter_messages(source_entity, min_id=start_msg_id - 1, reverse=True):
            if msg.id >= start_msg_id:
                messages.append(msg)

        total = len(messages)
        log.info(f"📦 [{job_key}] {total} pesan ditemukan")

        with state.lock:
            state.bulk_jobs[job_key]["total"]  = total
            state.bulk_jobs[job_key]["status"] = "running"

        for i, msg in enumerate(messages):
            # Cek stop
            with state.lock:
                job = state.bulk_jobs.get(job_key, {})
                if job.get("stopped"):
                    log.info(f"⛔ Bulk [{job_key}] dihentikan.")
                    break

            # Cek pause — tunggu sampai di-resume
            while True:
                with state.lock:
                    paused = state.bulk_jobs.get(job_key, {}).get("paused", False)
                if not paused:
                    break
                await asyncio.sleep(1)

            try:
                await client.forward_messages(
                    entity=target_entity,
                    messages=msg,
                    from_peer=source_entity,
                )
                with state.lock:
                    state.bulk_jobs[job_key]["done"]           = i + 1
                    state.bulk_jobs[job_key]["current_msg_id"] = msg.id
                    state.stats[job_key] += 1
                log.info(f"📨 Bulk [{job_key}] {i+1}/{total} msg_id={msg.id}")
                if i < total - 1:
                    await asyncio.sleep(interval_sec)
            except Exception as e:
                log.error(f"❌ Bulk [{job_key}] msg {msg.id}: {e}")
                await asyncio.sleep(5)

        with state.lock:
            if not state.bulk_jobs[job_key].get("stopped"):
                state.bulk_jobs[job_key]["status"] = "done"
        log.info(f"✅ Bulk [{job_key}] selesai!")

    except Exception as e:
        log.error(f"❌ Bulk error [{job_key}]: {e}")
        with state.lock:
            if job_key in state.bulk_jobs:
                state.bulk_jobs[job_key]["status"] = "error"

# ─────────────────────────────────────────────────────────
#  FLASK APP
# ─────────────────────────────────────────────────────────

app = Flask(__name__)
app.secret_key = SECRET_KEY
CORS(app, supports_credentials=True, origins="*")

def auth(f):
    @wraps(f)
    def wrap(*a, **kw):
        tok = request.headers.get("X-Auth-Token")
        if tok != SECRET_KEY + DASHBOARD_PASSWORD:
            return jsonify({"ok": False, "msg": "Unauthorized"}), 401
        return f(*a, **kw)
    return wrap

@app.route('/')
def index():
    return send_from_directory('static', 'index.html')

@app.route("/api/login", methods=["POST"])
def api_login():
    d = request.json or {}
    if d.get("username") == DASHBOARD_USER and d.get("password") == DASHBOARD_PASSWORD:
        return jsonify({"ok": True, "token": SECRET_KEY + DASHBOARD_PASSWORD})
    return jsonify({"ok": False, "msg": "Username atau password salah"}), 401

@app.route("/api/status")
@auth
def api_status():
    uptime = None
    if state.start_time and state.running:
        d = datetime.now() - state.start_time
        h, r = divmod(int(d.total_seconds()), 3600)
        m, s = divmod(r, 60)
        uptime = f"{h:02d}:{m:02d}:{s:02d}"
    rules_out = []
    for rule in state.resolved:
        targets_out = []
        for tgt in rule.get("targets", []):
            key = f"rule_{rule['index']}_target_{tgt['index']}"
            targets_out.append({
                "username"    : tgt["username"],
                "title"       : tgt["title"],
                "enabled"     : tgt["enabled"],
                "interval_min": tgt["interval_min"],
                "delay_sec"   : tgt["delay_sec"],
                "forwarded"   : state.stats.get(key, 0),
            })
        rules_out.append({
            "index"       : rule["index"],
            "enabled"     : rule.get("enabled", False),
            "source"      : rule.get("source", ""),
            "source_title": rule.get("source_title", ""),
            "targets"     : targets_out,
        })
    return jsonify({
        "running"      : state.running,
        "connected"    : state.connected,
        "uptime"       : uptime,
        "me"           : state.me,
        "total_forward": sum(state.stats.values()),
        "error_count"  : len(state.errors),
        "rules"        : rules_out,
    })

@app.route("/api/bot/start", methods=["POST"])
@auth
def api_start():
    global bot_thread
    if state.running:
        return jsonify({"ok": False, "msg": "Bot sudah berjalan."})
    if not config.get("api_id") or not config.get("api_hash"):
        return jsonify({"ok": False, "msg": "API ID / Hash belum diisi."})
    state.start_time = datetime.now()
    state.running    = True
    bot_thread = threading.Thread(target=_bot_thread_fn, daemon=True)
    bot_thread.start()
    return jsonify({"ok": True, "msg": "Bot dimulai."})

@app.route("/api/bot/stop", methods=["POST"])
@auth
def api_stop():
    if not state.running:
        return jsonify({"ok": False, "msg": "Bot tidak berjalan."})
    stop_bot_now()
    return jsonify({"ok": True, "msg": "Bot dihentikan."})

@app.route("/api/bot/restart", methods=["POST"])
@auth
def api_restart():
    stop_bot_now()
    time.sleep(2)
    global bot_thread
    state.start_time = datetime.now()
    state.running    = True
    bot_thread = threading.Thread(target=_bot_thread_fn, daemon=True)
    bot_thread.start()
    return jsonify({"ok": True, "msg": "Bot direstart."})

@app.route("/api/logs")
@auth
def api_logs():
    return jsonify(state.get_logs(int(request.args.get("n", 150))))

@app.route("/api/errors")
@auth
def api_errors():
    return jsonify(state.get_errors())

@app.route("/api/config", methods=["GET"])
@auth
def api_config_get():
    safe = dict(config)
    safe.pop("api_hash", None)
    return jsonify(safe)

@app.route("/api/config/credentials", methods=["POST"])
@auth
def api_config_creds():
    d = request.json or {}
    config["api_id"]   = str(d.get("api_id", ""))
    config["api_hash"] = d.get("api_hash", "")
    save_config(config)
    return jsonify({"ok": True})

@app.route("/api/config/filter", methods=["POST"])
@auth
def api_config_filter():
    config["global_filter"] = (request.json or {}).get("filter", {})
    save_config(config)
    return jsonify({"ok": True})

@app.route("/api/rules", methods=["GET"])
@auth
def api_rules_get():
    return jsonify(config.get("rules", []))

@app.route("/api/rules", methods=["POST"])
@auth
def api_rules_save():
    config["rules"] = (request.json or {}).get("rules", [])
    save_config(config)
    return jsonify({"ok": True, "msg": "Rules disimpan. Restart bot agar berlaku."})

@app.route("/api/profile/update", methods=["POST"])
@auth
def api_profile_update():
    if not state.connected or not client or not bot_loop:
        return jsonify({"ok": False, "msg": "Bot tidak terhubung."})
    d = request.json or {}
    async def _update():
        await client(UpdateProfileRequest(
            first_name=d.get("first_name", ""),
            last_name=d.get("last_name", ""),
            about=d.get("bio", "")
        ))
        me = await client.get_me()
        state.me = {"id": me.id, "name": f"{me.first_name or ''} {me.last_name or ''}".strip(),
                    "username": me.username or "", "phone": me.phone or ""}
    fut = asyncio.run_coroutine_threadsafe(_update(), bot_loop)
    try:
        fut.result(timeout=10)
        return jsonify({"ok": True, "msg": "Profil diperbarui."})
    except Exception as e:
        return jsonify({"ok": False, "msg": str(e)})

@app.route("/api/profile/photo", methods=["POST"])
@auth
def api_profile_photo():
    if not state.connected or not client or not bot_loop:
        return jsonify({"ok": False, "msg": "Bot tidak terhubung."})
    b64 = (request.json or {}).get("image_base64", "")
    if not b64:
        return jsonify({"ok": False, "msg": "Tidak ada gambar."})
    img_bytes = base64.b64decode(b64.split(",")[-1])
    async def _upload():
        file = await client.upload_file(img_bytes, file_name="photo.jpg")
        await client(UploadProfilePhotoRequest(file=file))
    fut = asyncio.run_coroutine_threadsafe(_upload(), bot_loop)
    try:
        fut.result(timeout=15)
        return jsonify({"ok": True, "msg": "Foto profil diperbarui."})
    except Exception as e:
        return jsonify({"ok": False, "msg": str(e)})

# ── BULK FORWARD API ────────────────────────────────────

@app.route("/api/bulk/single", methods=["POST"])
@auth
def api_bulk_single():
    """
    Forward 1 pesan spesifik (dari link t.me/channel/ID) ke semua target di rule.
    Body: {rule_index, msg_id, delay_sec}
    """
    if not state.connected or not client or not bot_loop:
        return jsonify({"ok": False, "msg": "Bot tidak terhubung. Start bot dulu."})

    d         = request.json or {}
    rule_idx  = int(d.get("rule_index", 0))
    msg_id    = int(d.get("msg_id", 0))
    delay_sec = int(d.get("delay_sec", 5))

    if not msg_id:
        return jsonify({"ok": False, "msg": "msg_id tidak valid."})

    rule = next((r for r in state.resolved if r["index"] == rule_idx), None)
    if not rule:
        return jsonify({"ok": False, "msg": "Rule tidak ditemukan. Pastikan bot sudah start."})

    async def _forward_single():
        try:
            targets = [t for t in rule.get("targets", []) if t.get("enabled")]
            log.info(f"📤 Single forward msg_id={msg_id} ke {len(targets)} grup")
            for i, tgt in enumerate(targets):
                try:
                    await client.forward_messages(
                        entity=tgt["entity"],
                        messages=msg_id,
                        from_peer=rule["source_entity"],
                    )
                    key = f"rule_{rule_idx}_target_{tgt['index']}"
                    with state.lock:
                        state.stats[key] += 1
                        job_key = f"single_{rule_idx}_{msg_id}"
                        state.bulk_jobs[job_key]["done"] += 1
                    log.info(f"📨 Single [{rule['source_title']}] → [{tgt['title']}]")
                except Exception as e:
                    log.error(f"❌ Single forward gagal [{tgt['username']}]: {e}")
                if i < len(targets) - 1:
                    await asyncio.sleep(delay_sec)

            job_key = f"single_{rule_idx}_{msg_id}"
            with state.lock:
                if job_key in state.bulk_jobs:
                    state.bulk_jobs[job_key]["status"] = "done"
            log.info(f"✅ Single forward selesai msg_id={msg_id}")
        except Exception as e:
            log.error(f"❌ Single forward error: {e}")

    job_key = f"single_{rule_idx}_{msg_id}"
    with state.lock:
        state.bulk_jobs[job_key] = {
            "type"    : "single",
            "status"  : "running",
            "msg_id"  : msg_id,
            "source"  : rule["source_title"],
            "target"  : f"{len(rule.get('targets',[]))} grup",
            "total"   : len([t for t in rule.get("targets",[]) if t.get("enabled")]),
            "done"    : 0,
            "paused"  : False,
            "stopped" : False,
        }

    asyncio.run_coroutine_threadsafe(_forward_single(), bot_loop)
    return jsonify({"ok": True, "msg": f"Forwarding pesan #{msg_id} ke {len(rule.get('targets',[]))} grup..."})


@app.route("/api/bulk/start", methods=["POST"])
@auth
def api_bulk_start():
    if not state.connected or not client or not bot_loop:
        return jsonify({"ok": False, "msg": "Bot tidak terhubung. Start bot dulu."})
    d            = request.json or {}
    rule_idx     = int(d.get("rule_index", 0))
    start_msg_id = int(d.get("start_msg_id", 1))
    interval_sec = int(d.get("interval_sec", 300))

    rule = next((r for r in state.resolved if r["index"] == rule_idx), None)
    if not rule:
        return jsonify({"ok": False, "msg": "Rule tidak ditemukan. Pastikan bot sudah start."})
    if not rule.get("enabled"):
        return jsonify({"ok": False, "msg": "Rule ini nonaktif."})

    started = []
    for tgt in rule.get("targets", []):
        if not tgt.get("enabled"):
            continue
        job_key = f"bulk_{rule_idx}_{tgt['index']}"
        with state.lock:
            if state.bulk_jobs.get(job_key, {}).get("status") == "running":
                continue
            state.bulk_jobs[job_key] = {
                "status"        : "starting",
                "total"         : 0,
                "done"          : 0,
                "current_msg_id": start_msg_id,
                "paused"        : False,
                "stopped"       : False,
                "target"        : tgt["title"],
                "source"        : rule["source_title"],
                "interval_sec"  : interval_sec,
                "start_msg_id"  : start_msg_id,
            }
        asyncio.run_coroutine_threadsafe(
            bulk_forward_task(rule_idx, tgt["index"], rule["source_entity"],
                              tgt["entity"], start_msg_id, interval_sec, job_key),
            bot_loop
        )
        started.append(tgt["title"])

    if not started:
        return jsonify({"ok": False, "msg": "Tidak ada target aktif atau semua sudah berjalan."})
    return jsonify({"ok": True, "msg": f"Bulk dimulai ke {len(started)} grup: {', '.join(started)}"})

@app.route("/api/bulk/status")
@auth
def api_bulk_status():
    with state.lock:
        # Hapus entity dari output (tidak JSON-serializable)
        out = {}
        for k, v in state.bulk_jobs.items():
            out[k] = {x: v[x] for x in v if x not in ("entity",)}
        return jsonify(out)

@app.route("/api/bulk/pause", methods=["POST"])
@auth
def api_bulk_pause():
    job_key = (request.json or {}).get("job_key")
    with state.lock:
        if job_key in state.bulk_jobs:
            state.bulk_jobs[job_key]["paused"] = True
    return jsonify({"ok": True})

@app.route("/api/bulk/resume", methods=["POST"])
@auth
def api_bulk_resume():
    job_key = (request.json or {}).get("job_key")
    with state.lock:
        if job_key in state.bulk_jobs:
            state.bulk_jobs[job_key]["paused"] = False
    return jsonify({"ok": True})

@app.route("/api/bulk/stop", methods=["POST"])
@auth
def api_bulk_stop():
    job_key = (request.json or {}).get("job_key")
    with state.lock:
        if job_key in state.bulk_jobs:
            state.bulk_jobs[job_key]["stopped"] = True
            state.bulk_jobs[job_key]["status"]  = "stopped"
    return jsonify({"ok": True})

@app.route("/api/bulk/stop_all", methods=["POST"])
@auth
def api_bulk_stop_all():
    with state.lock:
        for key in state.bulk_jobs:
            state.bulk_jobs[key]["stopped"] = True
            state.bulk_jobs[key]["status"]  = "stopped"
    return jsonify({"ok": True, "msg": "Semua bulk dihentikan."})

# ─────────────────────────────────────────────────────────
#  MAIN
# ─────────────────────────────────────────────────────────

if __name__ == "__main__":
    os.makedirs("static", exist_ok=True)
    print("=" * 50)
    print("  TG Forwarder Pro — Running at :5000")
    print("=" * 50)
    app.run(host="0.0.0.0", port=5000, debug=False, threaded=True)
