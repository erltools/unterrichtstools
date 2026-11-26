/* Gemeinsamer Browser-Teil für Tools mit Runden (Wortwolke, Abstimmung).
   Braucht vorher ../gemeinsam/qrcode.js. Stellt window.Runde bereit:
     const v = Runde.verbindung('api.php', demoCall);  // demoCall(action, daten) für die Vorschau ohne Server
     await v.pruefen();                                   // true, wenn der Server antwortet, sonst Vorschau-Modus
     await v.call('get', { r: code }, 'GET');
   Außerdem: Runde.store, Runde.qrSvg, Runde.studentUrl, Runde.shortUrl, Runde.deviceId, Runde.wachHalten. */
(function () {
'use strict';

const store = {
  get(k) { try { return JSON.parse(localStorage.getItem(k)); } catch (e) { return null; } },
  set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} },
  del(k) { try { localStorage.removeItem(k); } catch (e) {} }
};

function verbindung(api, demoCall) {
  const v = {
    demo: false,
    async pruefen() {
      try {
        const r = await fetch(api + '?action=ping', { cache: 'no-store' });
        const j = await r.json();
        v.demo = !j.ok;
      } catch (e) { v.demo = true; }
      return !v.demo;
    },
    async call(action, data, method) {
      if (v.demo) return demoCall(action, data || {});
      const opts = { method: method || 'POST', headers: { 'Content-Type': 'application/json' }, cache: 'no-store' };
      let url = api;
      if (opts.method === 'GET') {
        url += '?' + new URLSearchParams(Object.assign({ action }, data || {})).toString();
        delete opts.headers;
      } else {
        opts.body = JSON.stringify(Object.assign({ action }, data || {}));
      }
      let res;
      try { res = await fetch(url, opts); } catch (e) { throw new Error('Keine Verbindung. Bitte WLAN prüfen.'); }
      let json;
      try { json = await res.json(); } catch (e) { throw new Error('Der Server antwortet nicht richtig.'); }
      if (!json.ok) { const err = new Error(json.error || 'Fehler'); err.status = res.status; throw err; }
      return json;
    }
  };
  return v;
}

function qrSvg(text) {
  const qr = qrcode(0, 'M');
  qr.addData(unescape(encodeURIComponent(text)), 'Byte');
  qr.make();
  return qr.createSvgTag({ cellSize: 8, margin: 2, scalable: true });
}
function studentUrl(code) {
  const path = location.pathname.replace(/index\.html?$/, '');
  return location.origin + path + '?r=' + code;
}
function shortUrl() {
  return (location.host + location.pathname.replace(/index\.html?$/, '')).replace(/\/$/, '');
}

// Zufällige Kennung dieses Geräts (nur lokal gespeichert, der Server speichert davon nur einen Hash pro Runde).
function deviceId(key) {
  let id = store.get(key);
  if (!id) { id = Array.from(crypto.getRandomValues(new Uint8Array(12)), (b) => b.toString(16).padStart(2, '0')).join(''); store.set(key, id); }
  return id;
}

// Bildschirm wach halten, solange eine Runde läuft (wo der Browser das kann).
let wakeLock = null;
async function wachHalten() {
  try { if ('wakeLock' in navigator && !wakeLock) { wakeLock = await navigator.wakeLock.request('screen'); wakeLock.addEventListener('release', () => { wakeLock = null; }); } } catch (e) {}
}

window.Runde = { store, verbindung, qrSvg, studentUrl, shortUrl, deviceId, wachHalten };
})();
