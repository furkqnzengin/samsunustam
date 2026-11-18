"""Reels için müzik ve ses efekti sentezleyici (sadece numpy). Tüm sesler burada üretilir; telifli materyal yoktur.
Kullanım: python3 ses.py <cues.json> <cikis.wav> <stil>   (stil: parlak | ritmik | sicak)"""
import json, sys, wave
import numpy as np

SR = 44100
rng = np.random.default_rng(7)


def t_arr(sure):
    return np.arange(int(sure * SR)) / SR


def env_exp(n, tau):
    return np.exp(-np.arange(n) / (tau * SR))


def adsr(n, a=.01, r=.1):
    e = np.ones(n)
    na, nr = int(a * SR), int(r * SR)
    if na: e[:na] = np.linspace(0, 1, na)
    if nr: e[-nr:] *= np.linspace(1, 0, nr)
    return e


def notaf(m):  # MIDI -> Hz
    return 440 * 2 ** ((m - 69) / 12)


def ekle(buf, sig, t0, gain=1.0):
    i = int(t0 * SR)
    if i < 0: sig, i = sig[-i:], 0  # başlangıçtan önce kalan kısmı kes
    if i >= len(buf) or len(sig) == 0: return
    j = min(len(buf), i + len(sig))
    buf[i:j] += sig[:j - i] * gain


def fft_filtre(x, lo, hi):
    X = np.fft.rfft(x); f = np.fft.rfftfreq(len(x), 1 / SR)
    X[(f < lo) | (f > hi)] = 0
    return np.fft.irfft(X, len(x))


# ------------------------------------------------------------ davul
def kick():
    n = int(.42 * SR); tt = np.arange(n) / SR
    f = 45 + 110 * np.exp(-tt / .035)
    ph = 2 * np.pi * np.cumsum(f) / SR
    s = np.sin(ph) * env_exp(n, .16)
    s[:120] += rng.standard_normal(120) * .3 * np.linspace(1, 0, 120)
    return np.tanh(s * 1.6)


def clap():
    n = int(.25 * SR); s = np.zeros(n)
    nz = np.diff(rng.standard_normal(n + 1))
    for k, d in enumerate([0, .011, .022]):
        i = int(d * SR); m = n - i
        s[i:] += nz[:m] * env_exp(m, .012 if k < 2 else .07)
    s = fft_filtre(s, 700, 7000)  # sert tizleri ve gümbürtüyü al
    s += np.sin(2 * np.pi * 210 * np.arange(n) / SR) * env_exp(n, .03) * .3
    return s * .3


def hat(acik=False):
    n = int((.18 if acik else .05) * SR)
    nz = np.diff(np.diff(rng.standard_normal(n + 2)))
    return nz * env_exp(n, .05 if acik else .012) * .18


# ------------------------------------------------------------ enstrümanlar
def bas(f, sure):
    n = int(sure * SR); tt = np.arange(n) / SR
    s = sum(np.sin(2 * np.pi * f * k * tt) / k ** 2 * (1 if k % 2 else .5) for k in range(1, 7))
    return s * env_exp(n, sure * .7) * adsr(n, .005, .03) * .55


def pad(notalar, sure):
    n = int(sure * SR); tt = np.arange(n) / SR; s = np.zeros(n)
    for m in notalar:
        for det in (-.12, .12):
            f = notaf(m + det)
            s += sum(np.sin(2 * np.pi * f * k * tt + k) / k for k in range(1, 6)) * .5
    s *= adsr(n, .25, .4) * (1 + .15 * np.sin(2 * np.pi * 4 * tt)) / len(notalar)
    return s * .16


def pluck(f, sure=.5, parlak=1.0):
    n = int(sure * SR); tt = np.arange(n) / SR
    s = np.sin(2 * np.pi * f * tt) + .35 * parlak * np.sin(4 * np.pi * f * tt) * env_exp(n, .06) + .12 * np.sin(6 * np.pi * f * tt) * env_exp(n, .03)
    return s * env_exp(n, .18) * adsr(n, .002, .05) * .32


# ------------------------------------------------------------ efektler
def whoosh(sure=.5, yukari=True):
    n = int(sure * SR); nz = rng.standard_normal(n); out = np.zeros(n)
    W = 2048; H = 512; win = np.hanning(W)
    for i in range(0, n - W, H):
        p = i / max(1, n - W); p = p if yukari else 1 - p
        c = 300 * (14 ** p)
        out[i:i + W] += fft_filtre(nz[i:i + W] * win, c * .6, c * 1.6) * win
    e = np.sin(np.pi * np.linspace(0, 1, n)) ** 1.5
    return out * e * .9


def pop_s():
    n = int(.12 * SR); tt = np.arange(n) / SR
    f = 280 + 700 * np.exp(-tt / .018)
    return np.sin(2 * np.pi * np.cumsum(f) / SR) * env_exp(n, .035) * .7


def tik():
    n = int(.012 * SR)
    s = np.diff(rng.standard_normal(n + 1)) * env_exp(n, .002) * .35
    s += np.sin(2 * np.pi * 2400 * np.arange(n) / SR) * env_exp(n, .003) * .15
    return s


def zil(f=1318.5, sure=1.4, g=.5):
    n = int(sure * SR); tt = np.arange(n) / SR
    s = sum(a * np.sin(2 * np.pi * f * k * tt) * env_exp(n, d) for k, a, d in [(1, 1, .5), (2.01, .45, .25), (3.02, .25, .12), (4.2, .15, .07)])
    return s * adsr(n, .002, .1) * g


def kasa():
    s = np.zeros(int(1.0 * SR))
    mek = fft_filtre(rng.standard_normal(int(.08 * SR)), 800, 6000) * env_exp(int(.08 * SR), .02) * .5
    s[:len(mek)] += mek
    for i, f in enumerate([2093, 2637]):
        z = zil(f, .7, .35); o = int((.07 + i * .09) * SR); s[o:o + len(z)] += z[:len(s) - o]
    return s


def parilti():
    s = np.zeros(int(1.8 * SR))
    for i, m in enumerate([84, 88, 91, 96, 100, 103]):
        p = pluck(notaf(m), .9, .6) * .9; o = int(i * .055 * SR); s[o:o + len(p)] += p[:len(s) - o]
    for d, g in [(.11, .4), (.23, .25), (.37, .15)]:  # basit yankı
        o = int(d * SR); s[o:] += s[:-o] * g
    return s * .8


def efekt_ekle(buf, t0, ad):
    if ad == 'whoosh': ekle(buf, whoosh(.5), t0 - .25, .55)
    elif ad == 'pop': ekle(buf, pop_s(), t0, .6)
    elif ad == 'click': ekle(buf, tik(), t0, 1.2)
    elif ad == 'ding': ekle(buf, zil(), t0, .55)
    elif ad == 'kasa': ekle(buf, kasa(), t0, .6)
    elif ad == 'shimmer': ekle(buf, parilti(), t0, .55)
    elif ad.startswith('yazi:'):
        d = float(ad.split(':')[1]); t = t0
        while t < t0 + d:
            ekle(buf, tik(), t, .9 + rng.random() * .4); t += .06 + rng.random() * .05
    elif ad.startswith('say:'):
        d = float(ad.split(':')[1]); k = 0
        while True:
            p = k / 22; tt = t0 + d * (1 - (1 - p) ** 2)
            if p > 1: break
            ekle(buf, tik(), tt, .8); k += 1


# ------------------------------------------------------------ müzik
STILLER = {
    # bpm, akorlar (MIDI kök + akor tipi), melodi deseni (akor tonları indeksleri), parlaklık
    'parlak': dict(bpm=112, kok=[60, 55, 57, 53], tip=['maj', 'maj', 'min', 'maj'], desen=[0, 2, 1, 2, 3, 2, 1, 2], parlak=1.0, acikhat=True),
    'ritmik': dict(bpm=120, kok=[57, 53, 60, 55], tip=['min', 'maj', 'maj', 'maj'], desen=[0, 1, 2, 3, 2, 1, 3, 1], parlak=1.2, acikhat=True),
    'sicak': dict(bpm=104, kok=[53, 48, 50, 46], tip=['maj', 'maj', 'min', 'maj'], desen=[2, 1, 0, 1, 2, 3, 2, 1], parlak=.7, acikhat=False),
}
TIP = {'maj': [0, 4, 7, 12], 'min': [0, 3, 7, 12]}


def muzik(sure, stil):
    S = STILLER[stil]; beat = 60 / S['bpm']; bar = 4 * beat
    n = int(sure * SR); bus = {k: np.zeros(n) for k in ('davul', 'bas', 'pad', 'lead')}
    K, C, H, HO = kick(), clap(), hat(), hat(True)
    nb = int(np.ceil(sure / bar)) + 1
    for b in range(nb):
        t0 = b * bar; ci = b % 4
        kok = S['kok'][ci]; ton = [kok + x for x in TIP[S['tip'][ci]]]
        intro = b == 0
        for q in range(4):
            tq = t0 + q * beat
            if not intro or q >= 2: ekle(bus['davul'], K, tq, .9 if q in (0, 2) or S['bpm'] >= 112 else .6)
            if q in (1, 3) and not intro: ekle(bus['davul'], C, tq, .7)
            for e in (0, .5):
                if not intro: ekle(bus['davul'], HO if (S['acikhat'] and e == .5 and q == 3) else H, tq + e * beat, 1)
            # bas: 8'lik notalar
            for e in (0, .5):
                if q == 3 and e == .5: f = notaf(ton[1] - 12)
                else: f = notaf(kok - 12)
                ekle(bus['bas'], bas(f, beat * .48), tq + e * beat, 1)
        ekle(bus['pad'], pad([m + 12 for m in ton[:3]], bar), t0, 1)
        if b >= 1:  # melodi ikinci ölçüden itibaren
            for k, idx in enumerate(S['desen']):
                ekle(bus['lead'], pluck(notaf(ton[idx] + 24), .5, S['parlak']), t0 + k * beat / 2, 1)
    # yankı (melodi)
    d = int(beat * .75 * SR); lead = bus['lead'].copy(); lead[d:] += bus['lead'][:-d] * .35
    # kick'e göre kısma (sidechain)
    duck = np.ones(n); kenv = 1 - .55 * env_exp(int(beat * SR), beat * .25)
    for b in range(nb):
        for q in range(4):
            i = int((b * bar + q * beat) * SR); j = min(n, i + len(kenv))
            if i < n: duck[i:j] = np.minimum(duck[i:j], kenv[:j - i])
    mix = bus['davul'] * .8 + (bus['bas'] * .9 + bus['pad'] * 1.0 + lead * .7) * duck
    # son: final akoru
    son = sure - 1.6
    fin = pad([S['kok'][0] + 12 + x for x in (0, 7, 16)], 1.6) * 1.4
    ekle(mix, fin, son, 1)
    return mix


def yap(cues_yol, cikis, stil):
    j = json.load(open(cues_yol)); sure = float(j['sure'])
    m = muzik(sure, stil)
    m = m / (np.max(np.abs(m)) + 1e-9) * .55
    fx = np.zeros_like(m)
    for t0, ad in j['cues']: efekt_ekle(fx, float(t0), ad)
    out = m + fx * .9
    # giriş-çıkış yumuşatma, yumuşak sınırlayıcı
    fi, fo = int(.25 * SR), int(1.0 * SR)
    out[:fi] *= np.linspace(0, 1, fi); out[-fo:] *= np.linspace(1, 0, fo)
    out = np.tanh(out * 1.3) / np.tanh(1.3)
    out = out / (np.max(np.abs(out)) + 1e-9) * .89
    st = np.stack([out, np.roll(out, 9) * .98], 1)  # hafif stereo genişlik
    pcm = (st * 32767).astype('<i2')
    with wave.open(cikis, 'wb') as w:
        w.setnchannels(2); w.setsampwidth(2); w.setframerate(SR); w.writeframes(pcm.tobytes())


if __name__ == '__main__':
    yap(sys.argv[1], sys.argv[2], sys.argv[3])
