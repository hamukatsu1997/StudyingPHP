<?php
// シンプルなピアノ鍵盤サイト（PHP単体）
// 音の再生はブラウザの Web Audio API を使用します。
$siteTitle = 'Simple Piano';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    :root {
      --bg: #111827;
      --panel: #1f2937;
      --accent: #60a5fa;
      --white-key-width: min(10vw, 76px);
      --white-key-height: min(48vw, 340px);
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 24px 12px;
      color: #f9fafb;
      background: radial-gradient(circle at top, #26344d, var(--bg) 60%);
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }
    .app { width: min(100%, 780px); text-align: center; }
    h1 { margin: 0 0 8px; font-size: clamp(1.5rem, 4vw, 2.25rem); }
    .hint { margin: 0 0 22px; color: #cbd5e1; font-size: .95rem; }
    .piano-wrap {
      overflow-x: auto;
      padding: 18px 10px 24px;
      border-radius: 18px;
      background: rgba(17, 24, 39, .78);
      box-shadow: 0 18px 45px rgba(0,0,0,.3);
    }
    .piano {
      position: relative;
      display: flex;
      width: max-content;
      height: var(--white-key-height);
      margin: 0 auto;
      user-select: none;
      touch-action: none;
    }
    .key {
      position: relative;
      border: 1px solid #94a3b8;
      cursor: pointer;
      transition: background .06s, transform .06s;
    }
    .white {
      width: var(--white-key-width);
      height: 100%;
      z-index: 1;
      border-radius: 0 0 8px 8px;
      background: linear-gradient(90deg, #fff, #e5e7eb);
      box-shadow: inset 0 -8px 0 rgba(15,23,42,.1);
    }
    .white.active { background: linear-gradient(90deg, #bfdbfe, var(--accent)); transform: translateY(3px); }
    .black {
      position: absolute;
      top: 0;
      left: calc(var(--white-key-width) - min(2vw, 15px));
      width: min(4vw, 30px);
      height: 60%;
      z-index: 2;
      border: 1px solid #020617;
      border-radius: 0 0 5px 5px;
      background: linear-gradient(90deg, #334155, #020617 55%, #475569);
      box-shadow: 0 5px 5px rgba(0,0,0,.45);
    }
    .black.active { background: #2563eb; transform: translateY(3px); }
    .label {
      position: absolute;
      bottom: 10px;
      left: 0;
      right: 0;
      color: #475569;
      font-weight: 700;
      font-size: clamp(.65rem, 2vw, .85rem);
      pointer-events: none;
    }
    .black .label { display: none; }
    .status { margin-top: 16px; color: #93c5fd; min-height: 1.4em; }
    .controls { margin-top: 16px; color: #cbd5e1; font-size: .85rem; }
    @media (max-width: 560px) {
      :root { --white-key-width: 13vw; --white-key-height: 58vw; }
      .hint { font-size: .85rem; }
    }
  </style>
</head>
<body>
  <main class="app">
    <h1>🎹 Simple Piano</h1>
    <p class="hint">鍵盤をクリック・タップ、または A〜K キーで演奏できます</p>
    <section class="piano-wrap" aria-label="ピアノ鍵盤">
      <div class="piano" id="piano"></div>
    </section>
    <div class="status" id="status">鍵盤を押してください</div>
    <div class="controls">対応音域：C4〜C5　／　音声はブラウザ内で生成されます</div>
  </main>

  <script>
    const notes = [
      { name: 'C4',  freq: 261.63, type: 'white', key: 'a' },
      { name: 'C#4', freq: 277.18, type: 'black', key: 'w' },
      { name: 'D4',  freq: 293.66, type: 'white', key: 's' },
      { name: 'D#4', freq: 311.13, type: 'black', key: 'e' },
      { name: 'E4',  freq: 329.63, type: 'white', key: 'd' },
      { name: 'F4',  freq: 349.23, type: 'white', key: 'f' },
      { name: 'F#4', freq: 369.99, type: 'black', key: 't' },
      { name: 'G4',  freq: 392.00, type: 'white', key: 'g' },
      { name: 'G#4', freq: 415.30, type: 'black', key: 'y' },
      { name: 'A4',  freq: 440.00, type: 'white', key: 'h' },
      { name: 'A#4', freq: 466.16, type: 'black', key: 'u' },
      { name: 'B4',  freq: 493.88, type: 'white', key: 'j' },
      { name: 'C5',  freq: 523.25, type: 'white', key: 'k' }
    ];

    const piano = document.getElementById('piano');
    const status = document.getElementById('status');
    const elements = new Map();
    const activeVoices = new Map();
    let audioContext;

    function getAudioContext() {
      if (!audioContext) audioContext = new (window.AudioContext || window.webkitAudioContext)();
      if (audioContext.state === 'suspended') audioContext.resume();
      return audioContext;
    }

    function createKeys() {
      notes.forEach((note, index) => {
        const el = document.createElement('button');
        el.className = `key ${note.type}`;
        el.type = 'button';
        el.dataset.index = index;
        el.dataset.key = note.key;
        el.setAttribute('aria-label', `${note.name}（${note.key.toUpperCase()}）`);
        if (note.type === 'white') {
          const label = document.createElement('span');
          label.className = 'label';
          label.textContent = note.name;
          el.appendChild(label);
          piano.appendChild(el);
        } else {
          // 黒鍵は直前の白鍵を基準に配置
          const whiteBefore = [...piano.querySelectorAll('.white')].at(-1);
          whiteBefore.appendChild(el);
        }
        elements.set(index, el);
        el.addEventListener('pointerdown', event => {
          event.preventDefault();
          startNote(index);
        });
        el.addEventListener('pointerup', () => stopNote(index));
        el.addEventListener('pointerleave', () => stopNote(index));
      });
    }

    function startNote(index) {
      if (activeVoices.has(index)) return;
      const note = notes[index];
      const ctx = getAudioContext();
      const oscillator = ctx.createOscillator();
      const gain = ctx.createGain();
      oscillator.type = 'triangle';
      oscillator.frequency.value = note.freq;
      gain.gain.setValueAtTime(0.0001, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.28, ctx.currentTime + 0.015);
      oscillator.connect(gain).connect(ctx.destination);
      oscillator.start();
      activeVoices.set(index, { oscillator, gain });
      elements.get(index).classList.add('active');
      status.textContent = `${note.name} を再生中`;
    }

    function stopNote(index) {
      const voice = activeVoices.get(index);
      if (!voice) return;
      const ctx = getAudioContext();
      voice.gain.gain.cancelScheduledValues(ctx.currentTime);
      voice.gain.gain.setValueAtTime(Math.max(voice.gain.gain.value, 0.0001), ctx.currentTime);
      voice.gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.12);
      voice.oscillator.stop(ctx.currentTime + 0.13);
      activeVoices.delete(index);
      elements.get(index).classList.remove('active');
      status.textContent = '鍵盤を押してください';
    }

    const keyToIndex = new Map(notes.map((note, index) => [note.key, index]));
    window.addEventListener('keydown', event => {
      if (event.repeat) return;
      const index = keyToIndex.get(event.key.toLowerCase());
      if (index !== undefined) {
        event.preventDefault();
        startNote(index);
      }
    });
    window.addEventListener('keyup', event => {
      const index = keyToIndex.get(event.key.toLowerCase());
      if (index !== undefined) stopNote(index);
    });

    createKeys();
  </script>
</body>
</html>