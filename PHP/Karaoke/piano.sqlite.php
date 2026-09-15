<?php
session_start();
$dbFile = __DIR__ . '/piano.sqlite';
$db = new PDO('sqlite:' . $dbFile, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
$db->exec("CREATE TABLE IF NOT EXISTS performances (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, title TEXT NOT NULL, events_json TEXT NOT NULL, duration_ms INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(id))");

function jsonResponse(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function currentUser(): ?array {
    return isset($_SESSION['user_id'], $_SESSION['username'])
        ? ['id' => (int)$_SESSION['user_id'], 'username' => $_SESSION['username']]
        : null;
}
$action = $_POST['action'] ?? $_GET['action'] ?? null;
if ($action === 'register' || $action === 'login') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if (!preg_match('/^[\p{L}\p{N}_-]{2,30}$/u', $username) || strlen($password) < 4) {
        jsonResponse(['ok' => false, 'message' => 'ユーザー名は2〜30文字、パスワードは4文字以上で入力してください。'], 422);
    }
    if ($action === 'register') {
        try {
            $stmt = $db->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            $_SESSION['user_id'] = (int)$db->lastInsertId();
            $_SESSION['username'] = $username;
            jsonResponse(['ok' => true, 'user' => currentUser()]);
        } catch (PDOException $e) {
            jsonResponse(['ok' => false, 'message' => 'そのユーザー名はすでに使われています。'], 409);
        }
    }
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        jsonResponse(['ok' => false, 'message' => 'ユーザー名またはパスワードが違います。'], 401);
    }
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    jsonResponse(['ok' => true, 'user' => currentUser()]);
}
if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    jsonResponse(['ok' => true]);
}
if ($action === 'me') jsonResponse(['ok' => true, 'user' => currentUser()]);
if ($action === 'save') {
    $user = currentUser();
    if (!$user) jsonResponse(['ok' => false, 'message' => '保存にはログインが必要です。'], 401);
    $title = trim((string)($_POST['title'] ?? '無題の演奏'));
    $events = json_decode((string)($_POST['events'] ?? '[]'), true);
    $duration = max(0, (int)($_POST['duration_ms'] ?? 0));
    if ($title === '' || mb_strlen($title) > 80 || !is_array($events) || count($events) > 5000) {
        jsonResponse(['ok' => false, 'message' => '演奏名または演奏データが不正です。'], 422);
    }
    $stmt = $db->prepare('INSERT INTO performances (user_id, title, events_json, duration_ms) VALUES (?, ?, ?, ?)');
    $stmt->execute([$user['id'], $title, json_encode($events, JSON_UNESCAPED_UNICODE), $duration]);
    jsonResponse(['ok' => true, 'id' => (int)$db->lastInsertId()]);
}
if ($action === 'list') {
    $rows = $db->query('SELECT p.id, p.title, p.events_json, p.duration_ms, p.created_at, u.username FROM performances p JOIN users u ON u.id = p.user_id ORDER BY p.created_at DESC, p.id DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) $row['events'] = json_decode($row['events_json'], true) ?: [];
    unset($row);
    jsonResponse(['ok' => true, 'performances' => $rows]);
}
if ($action === 'delete') {
    $user = currentUser();
    if (!$user) jsonResponse(['ok' => false, 'message' => 'ログインが必要です。'], 401);
    $stmt = $db->prepare('DELETE FROM performances WHERE id = ? AND user_id = ?');
    $stmt->execute([(int)($_POST['id'] ?? 0), $user['id']]);
    jsonResponse(['ok' => true]);
}
$siteTitle = 'Simple Piano';
?><!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8') ?></title>
<style>
:root{--bg:#111827;--accent:#60a5fa;--white-key-width:36px;--white-key-height:min(42vw,260px)}*{box-sizing:border-box}body{margin:0;min-height:100vh;padding:24px 12px;color:#f9fafb;background:radial-gradient(circle at top,#26344d,var(--bg) 60%);font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.app{width:min(100%,1200px);margin:auto;text-align:center}h1{margin:0 0 8px;font-size:clamp(1.5rem,4vw,2.25rem)}.hint{margin:0 0 18px;color:#cbd5e1;font-size:.95rem}.panel{margin:14px auto;padding:14px;border:1px solid #334155;border-radius:14px;background:rgba(31,41,55,.8);text-align:left}.auth{display:flex;flex-wrap:wrap;gap:8px;align-items:center}.auth input,.savebox input{min-width:120px;padding:9px;border:1px solid #64748b;border-radius:7px;background:#0f172a;color:white}.btn{padding:9px 13px;border:0;border-radius:7px;color:#fff;background:#2563eb;cursor:pointer}.btn.secondary{background:#475569}.btn.danger{background:#b91c1c}.btn:disabled{opacity:.5;cursor:not-allowed}.who{margin-left:auto;color:#bfdbfe}.piano-wrap{overflow-x:auto;display:flex;justify-content:center;-webkit-overflow-scrolling:touch;padding:18px 10px 24px;border-radius:18px;background:rgba(17,24,39,.78);box-shadow:0 18px 45px rgba(0,0,0,.3)}.piano-wrap.is-overflowing{justify-content:flex-start}.piano{position:relative;display:flex;width:max-content;height:var(--white-key-height);margin:0;user-select:none;touch-action:none}.key{position:relative;border:1px solid #94a3b8;cursor:pointer;transition:background .06s,transform .06s}.white{width:var(--white-key-width);height:100%;z-index:1;border-radius:0 0 8px 8px;background:linear-gradient(90deg,#fff,#e5e7eb);box-shadow:inset 0 -8px 0 rgba(15,23,42,.1)}.white.active{background:linear-gradient(90deg,#bfdbfe,var(--accent));transform:translateY(3px)}.black{position:absolute;top:0;left:calc(var(--white-key-width) - 12px);width:23px;height:60%;z-index:2;border:1px solid #020617;border-radius:0 0 5px 5px;background:linear-gradient(90deg,#334155,#020617 55%,#475569);box-shadow:0 5px 5px rgba(0,0,0,.45)}.black.active{background:#2563eb;transform:translateY(3px)}.label{position:absolute;bottom:10px;left:0;right:0;color:#475569;font-weight:700;font-size:clamp(.58rem,2vw,.75rem);pointer-events:none}.black .label{display:none}.status{margin-top:14px;color:#93c5fd;min-height:1.4em}.controls{margin-top:10px;color:#cbd5e1;font-size:.85rem}.toolbar{display:flex;flex-wrap:wrap;gap:8px;justify-content:center}.savebox{display:flex;flex-wrap:wrap;gap:8px;align-items:center}.savebox input{flex:1}.section-title{margin:0 0 10px;font-size:1rem}.list{display:grid;gap:8px}.item{display:flex;flex-wrap:wrap;gap:8px;align-items:center;padding:10px;border-radius:8px;background:#0f172a}.item small{color:#cbd5e1}.item .spacer{flex:1}.empty{color:#94a3b8}@media(max-width:560px){:root{--white-key-width:32px;--white-key-height:48vw}.hint{font-size:.85rem}.who{width:100%;margin-left:0}}
</style></head><body><main class="app">
<h1>🎹 Simple Piano</h1><p class="hint">鍵盤をクリック・タップ、またはパソコンのキーで演奏できます</p>
<section class="panel"><div class="auth"><input id="username" placeholder="ユーザー名" maxlength="30"><input id="password" type="password" placeholder="パスワード"><button class="btn" id="register">新規登録</button><button class="btn secondary" id="login">ログイン</button><button class="btn danger" id="logout" hidden>ログアウト</button><span class="who" id="who">未ログイン</span></div><div class="status" id="authStatus"></div></section>
<section class="piano-wrap" aria-label="ピアノ鍵盤"><div class="piano" id="piano"></div></section>
<div class="status" id="status">鍵盤を押してください</div><div class="controls">対応音域：49鍵（C3〜C7）／横にスクロールできます</div>
<section class="panel"><h2 class="section-title">演奏の保存</h2><div class="toolbar"><button class="btn" id="record">● 録音開始</button><button class="btn secondary" id="stop" disabled>停止</button><span id="recordStatus">ログイン後に録音・保存できます</span></div><div class="savebox" style="margin-top:10px"><input id="title" placeholder="演奏名（例：練習曲1）" maxlength="80"><button class="btn" id="save" disabled>データベースに保存</button></div></section>
<section class="panel"><h2 class="section-title">お手本・みんなの演奏</h2><div class="toolbar"><button class="btn secondary" id="demo">▶ きらきら星を再生</button><button class="btn secondary" id="refresh">一覧を更新</button></div><div class="list" id="performanceList"><div class="empty">読み込み中…</div></div></section>
</main><script>
const blackSemitones=new Set([1,3,6,8,10]),keyboardMap=['z','s','x','d','c','v','g','b','h','n','j','m','q','2','w','3','e','r','5','t','6','y','7','u','i'];
function midiToNote(midi,index){const names=['C','C#','D','D#','E','F','F#','G','G#','A','A#','B'],semi=midi%12;return{name:`${names[semi]}${Math.floor(midi/12)-1}`,freq:440*Math.pow(2,(midi-69)/12),type:blackSemitones.has(semi)?'black':'white',key:keyboardMap[index]||null}}
const notes=Array.from({length:49},(_,i)=>midiToNote(i+48,i)),piano=document.getElementById('piano'),status=document.getElementById('status'),elements=new Map(),activeVoices=new Map();let audioContext;
function getAudioContext(){if(!audioContext)audioContext=new(window.AudioContext||window.webkitAudioContext)();if(audioContext.state==='suspended')audioContext.resume();return audioContext}
function updatePianoAlignment(){const w=document.querySelector('.piano-wrap');w.classList.toggle('is-overflowing',piano.scrollWidth>w.clientWidth)}
function createKeys(){notes.forEach((note,index)=>{const el=document.createElement('button');el.className=`key ${note.type}`;el.type='button';el.dataset.index=index;el.setAttribute('aria-label',note.name);if(note.type==='white'){const label=document.createElement('span');label.className='label';label.textContent=note.name;el.appendChild(label);piano.appendChild(el)}else{piano.querySelectorAll('.white').item(piano.querySelectorAll('.white').length-1).appendChild(el)}elements.set(index,el);el.addEventListener('pointerdown',e=>{e.preventDefault();startNote(index)});el.addEventListener('pointerup',()=>stopNote(index));el.addEventListener('pointerleave',()=>stopNote(index))})}
function startNote(index,record=true){if(activeVoices.has(index))return;const note=notes[index],ctx=getAudioContext(),osc=ctx.createOscillator(),gain=ctx.createGain();osc.type='triangle';osc.frequency.value=note.freq;gain.gain.setValueAtTime(.0001,ctx.currentTime);gain.gain.exponentialRampToValueAtTime(.28,ctx.currentTime+.015);osc.connect(gain).connect(ctx.destination);osc.start();activeVoices.set(index,{osc,gain});elements.get(index).classList.add('active');status.textContent=`${note.name} を再生中`;if(recording&&record)recording.events.push({index,time:performance.now()-recording.started})}
function stopNote(index,record=true){const voice=activeVoices.get(index);if(!voice)return;const ctx=getAudioContext();voice.gain.gain.cancelScheduledValues(ctx.currentTime);voice.gain.gain.setValueAtTime(Math.max(voice.gain.gain.value,.0001),ctx.currentTime);voice.gain.gain.exponentialRampToValueAtTime(.0001,ctx.currentTime+.12);voice.osc.stop(ctx.currentTime+.13);activeVoices.delete(index);elements.get(index).classList.remove('active');status.textContent='鍵盤を押してください'}
const keyToIndex=new Map(notes.map((n,i)=>[n.key,i]));window.addEventListener('keydown',e=>{if(e.repeat)return;const i=keyToIndex.get(e.key.toLowerCase());if(i!==undefined){e.preventDefault();startNote(i)}});window.addEventListener('keyup',e=>{const i=keyToIndex.get(e.key.toLowerCase());if(i!==undefined)stopNote(i)});
let recording=null,currentUser=null;
function api(action,data={}){const body=new URLSearchParams({action,...data});return fetch(location.href,{method:'POST',body}).then(r=>r.json())}
function setUser(user){currentUser=user;document.getElementById('who').textContent=user?`${user.username} でログイン中`:'未ログイン';document.getElementById('logout').hidden=!user;document.getElementById('register').disabled=!!user;document.getElementById('login').disabled=!!user;document.getElementById('record').disabled=!user;document.getElementById('save').disabled=!user||!recording?.events.length;document.getElementById('recordStatus').textContent=user?'録音できます':'ログイン後に録音・保存できます'}
function beginRecording(){if(!currentUser)return;recording={started:performance.now(),events:[]};document.getElementById('record').disabled=true;document.getElementById('stop').disabled=false;document.getElementById('recordStatus').textContent='録音中…鍵盤を演奏してください'}
function stopRecording(){if(!recording)return;recording.duration=Math.round(performance.now()-recording.started);document.getElementById('record').disabled=false;document.getElementById('stop').disabled=true;document.getElementById('save').disabled=!recording.events.length;document.getElementById('recordStatus').textContent=`${recording.events.length}音を記録しました。名前を入力して保存できます`}
function playEvents(events){if(!events?.length)return;const t0=performance.now();events.forEach(ev=>setTimeout(()=>startNote(ev.index,false),ev.time));events.forEach((ev,i)=>setTimeout(()=>stopNote(ev.index,false),ev.time+Math.min(450,events[i+1]?events[i+1].time-ev.time:350)))}
async function refreshList(){const r=await fetch('?action=list').then(x=>x.json()),list=document.getElementById('performanceList');list.innerHTML='';if(!r.performances.length){list.innerHTML='<div class="empty">保存された演奏はまだありません。</div>';return}r.performances.forEach(p=>{const row=document.createElement('div');row.className='item';const info=document.createElement('span');info.innerHTML=`<strong>${escapeHtml(p.title)}</strong> <small>by ${escapeHtml(p.username)}</small>`;const play=document.createElement('button');play.className='btn secondary';play.textContent='▶ 再生';play.onclick=()=>playEvents(p.events);row.append(info,play);if(currentUser&&currentUser.username===p.username){const del=document.createElement('button');del.className='btn danger';del.textContent='削除';del.onclick=async()=>{if(confirm('この演奏を削除しますか？')){await api('delete',{id:p.id});refreshList()}};row.append(del)}list.append(row)})}
function escapeHtml(v){return String(v).replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]))}
document.getElementById('register').onclick=async()=>{const r=await api('register',{username:username.value,password:password.value});authStatus.textContent=r.message||'';if(r.ok){setUser(r.user);refreshList()}};document.getElementById('login').onclick=async()=>{const r=await api('login',{username:username.value,password:password.value});authStatus.textContent=r.message||'';if(r.ok){setUser(r.user);refreshList()}};document.getElementById('logout').onclick=async()=>{await api('logout');setUser(null);refreshList()};document.getElementById('record').onclick=beginRecording;document.getElementById('stop').onclick=stopRecording;document.getElementById('save').onclick=async()=>{if(!recording?.events.length)return;const r=await api('save',{title:title.value||'無題の演奏',events:JSON.stringify(recording.events),duration_ms:recording.duration||0});recordStatus.textContent=r.ok?'保存しました。':'保存に失敗しました。';if(r.ok){recording=null;document.getElementById('save').disabled=true;refreshList()}};document.getElementById('demo').onclick=()=>playEvents([{index:0,time:0},{index:0,time:450},{index:7,time:900},{index:7,time:1350},{index:9,time:1800},{index:9,time:2250},{index:7,time:2700},{index:5,time:3600},{index:5,time:4050},{index:4,time:4500},{index:4,time:4950},{index:2,time:5400},{index:2,time:5850},{index:0,time:6300}]);document.getElementById('refresh').onclick=refreshList;createKeys();updatePianoAlignment();window.addEventListener('resize',updatePianoAlignment);fetch('?action=me').then(r=>r.json()).then(r=>{setUser(r.user);refreshList()});
</script></body></html>