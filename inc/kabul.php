<?php
declare(strict_types=1);

/*
 * Usta paneli servis kabul ekranlarının ortak parçaları:
 * adım sekmeleri, plaka rozeti, sektör alanları, kalem tablosu, hasar şeması, yakıt göstergesi, imza alanı.
 */

const KABUL_ADIMLARI = [
    'kart' => ['kabul-kart.php', 'Bilgiler'],
    'musteri' => ['kabul-musteri.php', 'Müşteri Bilgileri'],
    'talep' => ['kabul-talep.php', 'Servis Talepleri'],
    'form' => ['kabul-form.php', 'Servis Kabul Formu'],
    'islem' => ['servis-kayit.php', 'Servis İşlemleri'],
    'teslim' => ['kabul-teslim.php', 'Servis Teslim Formu'],
];

function plaka_rozet(?string $plaka, string $boy = ''): string
{
    $plaka = trim((string)$plaka);
    if ($plaka === '') return '';
    return '<span class="plaka ' . $boy . '"><i>TR</i>' . e(tr_upper($plaka)) . '</span>';
}

/** Kartın başlığında gösterilecek rozet: plakalı sektörlerde plaka, diğerlerinde başlık. */
function kart_rozet(array $kart, string $boy = ''): string
{
    $ozel = json_decode((string)($kart['ozel'] ?? ''), true) ?: [];
    if (!empty($ozel['plaka'])) return plaka_rozet($ozel['plaka'], $boy);
    // Plakasız kartlarda başlık zaten yanında yazılır; rozet tür ikonu + seri/anahtar gösterir
    $ikon = sektorler()[$kart['sektor'] ?? '']['ikon'] ?? '🔧';
    return '<span class="kartrozet ' . $boy . '">' . $ikon . (!empty($kart['anahtar']) ? ' ' . e($kart['anahtar']) : '') . '</span>';
}

/** Adım sekmeleri. $s yoksa (yeni kayıt) sadece ilk üç adım açıktır. */
function kabul_sekmeleri(?array $s, string $aktif, array $tanim): string
{
    $h = '<nav class="adimlar">';
    $i = 0;
    foreach (KABUL_ADIMLARI as $k => [$dosya, $ad]) {
        $i++;
        if ($k === 'kart') $ad = $tanim['kayit'] . ' Bilgileri';
        $acik = $s || in_array($k, ['kart', 'musteri', 'talep'], true);
        $href = $s ? $dosya . '?id=' . (int)$s['id'] : $dosya;
        $h .= $acik
            ? '<a class="' . ($k === $aktif ? 'on' : '') . '" href="' . e($href) . '"><b>' . $i . '</b>' . e($ad) . '</a>'
            : '<span class="kapali"><b>' . $i . '</b>' . e($ad) . '</span>';
    }
    return $h . '</nav>';
}

/** Sayfa başlığı: "Servis Kabul: [plaka] - Müşteri" */
function kabul_baslik(?array $s, ?array $kart, string $varsayilan): string
{
    if (!$s && !$kart) return $varsayilan;
    $r = $kart ? kart_rozet($kart) . (!empty((json_decode((string)$kart['ozel'], true) ?: [])['plaka']) ? '' : ' <b>' . e($kart['baslik']) . '</b>') : '';
    return $r . ($s ? ' <small class="muted">#' . (int)$s['no'] . ' · ' . e($s['musteri_ad']) . '</small>' : '');
}

/** Sektör kart alanı (etiket solda, giriş sağda). */
function kart_alani(array $a, string $v): string
{
    [$k, $et, $tip] = $a;
    $req = !empty($a[4]);
    $ank = !empty($a[5]);
    $ad = 'ozel[' . $k . ']';
    if ($tip === 'select') {
        $g = '<select class="inp" name="' . $ad . '"' . ($req ? ' required' : '') . '><option value="">Seçiniz</option>';
        foreach ($a[3] as $o) $g .= '<option' . ($v === $o ? ' selected' : '') . '>' . e($o) . '</option>';
        $g .= '</select>';
    } elseif ($tip === 'textarea') {
        $g = '<textarea class="inp" name="' . $ad . '" rows="3">' . e($v) . '</textarea>';
    } elseif ($tip === 'datalist') {
        $g = '<input class="inp" name="' . $ad . '" list="dl-' . e($a[3]) . '" value="' . e($v) . '" placeholder="Yazın ya da seçin" autocomplete="off">'
            . '<datalist id="dl-' . e($a[3]) . '">' . implode('', array_map(fn($m) => '<option value="' . e($m) . '">', marka_listesi($a[3]))) . '</datalist>';
    } else {
        $t = ['number' => 'number', 'date' => 'date'][$tip] ?? 'text';
        $buyuk = in_array($k, ['plaka', 'dorse', 'arac_plaka', 'sasi', 'motor_no'], true);
        $g = '<input class="inp' . ($ank ? ' anahtar' : '') . ($buyuk ? ' upper' : '') . '" name="' . $ad . '" type="' . $t . '"'
            . ($t === 'number' ? ' min="0" step="any"' : '') . ' value="' . e($v) . '"' . ($req ? ' required' : '') . ($ank ? ' autocomplete="off"' : '') . '>';
    }
    return '<label class="frow' . ($tip === 'textarea' ? ' wide' : '') . '"><span>' . e($et) . ($req ? ' *' : '') . '</span>' . $g . '</label>';
}

/** Kart alanlarını formdan okur; [ozel, anahtar, hata] döndürür. */
function kart_oku(array $tanim): array
{
    $ozel = [];
    $anahtar = null;
    $hata = '';
    foreach ($tanim['alanlar'] as $a) {
        [$k, $et, $tip] = $a;
        $v = mb_substr(trim((string)($_POST['ozel'][$k] ?? '')), 0, $tip === 'textarea' ? 2000 : 120);
        if ($tip === 'select' && $v !== '' && !in_array($v, $a[3], true)) $v = '';
        if ($tip === 'date' && $v !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) $v = '';
        if (in_array($k, ['plaka', 'dorse', 'arac_plaka', 'sasi', 'motor_no'], true)) $v = tr_upper($v);
        $ozel[$k] = $v;
        if (!empty($a[4]) && $v === '' && !$hata) $hata = "\"$et\" alanını doldurun.";
        if (!empty($a[5]) && $anahtar === null && $v !== '') $anahtar = anahtar_norm($v);
    }
    return [$ozel, $anahtar, $hata];
}

/** Formdaki kalemleri okur. */
function kalemleri_oku(): array
{
    $out = [];
    foreach ((array)($_POST['k_aciklama'] ?? []) as $i => $acik) {
        $acik = mb_substr(trim((string)$acik), 0, 200);
        if ($acik === '') continue;
        $adet = (float)str_replace(',', '.', (string)($_POST['k_adet'][$i] ?? '1'));
        $kdv = (int)($_POST['k_kdv'][$i] ?? 0);
        $birim = (string)($_POST['k_birim'][$i] ?? 'Adet');
        $out[] = [
            'tur' => isset(KALEM_TURLERI[$_POST['k_tur'][$i] ?? '']) ? $_POST['k_tur'][$i] : 'iscilik',
            'aciklama' => $acik, 'adet' => $adet > 0 ? round($adet, 2) : 1,
            'birim_fiyat' => para_oku((string)($_POST['k_fiyat'][$i] ?? '0')),
            'birim' => in_array($birim, KALEM_BIRIMLERI, true) ? $birim : 'Adet',
            'kdv' => in_array($kdv, KDV_ORANLARI, true) ? $kdv : 0,
        ];
    }
    return $out;
}

function kalemleri_kaydet(int $sid, array $kalemler): void
{
    q('DELETE FROM servis_kalemleri WHERE servis_id = ?', [$sid]);
    foreach ($kalemler as $i => $k) {
        q('INSERT INTO servis_kalemleri (servis_id, tur, aciklama, adet, birim_fiyat, birim, kdv, sira) VALUES (?,?,?,?,?,?,?,?)',
            [$sid, $k['tur'], $k['aciklama'], $k['adet'], $k['birim_fiyat'], $k['birim'], $k['kdv'], $i]);
    }
}

/** İşçilik/parça tablosu (form içinde). $kdvVars: yeni satırın varsayılan KDV'si. $servisKdv: eski kayıtların genel KDV'si. */
function kalem_editoru(array $kalemler, array $tanim, int $kdvVars, int $servisKdv = 0, float $iskonto = 0): string
{
    $fmt = fn($n) => number_format((float)$n, 2, ',', '.');
    $sel = function (string $ad, array $ops, $secili, array $etiket = []) {
        $h = '<select class="inp" name="' . $ad . '[]">';
        foreach ($ops as $k => $o) { $val = is_int($k) && !$etiket ? $o : $k; $h .= '<option value="' . e((string)$val) . '"' . ((string)$val === (string)$secili ? ' selected' : '') . '>' . e($etiket[$val] ?? (string)$o) . '</option>'; }
        return $h . '</select>';
    };
    $kdvEt = array_combine(KDV_ORANLARI, array_map(fn($k) => '%' . $k, KDV_ORANLARI));
    $satir = function (array $k) use ($sel, $fmt, $kdvEt, $kdvVars, $servisKdv) {
        $kdv = $k['kdv'] ?? ($k['id'] ?? null ? $servisKdv : $kdvVars);
        return '<div class="krow">'
            . $sel('k_tur', KALEM_TURLERI, $k['tur'] ?? 'iscilik')
            . '<input class="inp" name="k_aciklama[]" maxlength="200" placeholder="Ürün / hizmet adı" value="' . e((string)($k['aciklama'] ?? '')) . '">'
            . $sel('k_birim', KALEM_BIRIMLERI, $k['birim'] ?? 'Adet')
            . '<input class="inp num" name="k_adet[]" inputmode="decimal" value="' . e(rtrim(rtrim((string)($k['adet'] ?? '1'), '0'), '.') ?: '1') . '">'
            . '<input class="inp num" name="k_fiyat[]" inputmode="decimal" placeholder="0,00" value="' . (($k['birim_fiyat'] ?? '') !== '' ? $fmt($k['birim_fiyat']) : '') . '">'
            . $sel('k_kdv', $kdvEt, $kdv, $kdvEt)
            . '<b class="ktutar">0,00</b><button type="button" class="kdel" title="Sil">✕</button></div>';
    };
    $h = '<div class="hizli"><small class="muted">Hızlı ekle:</small>';
    foreach ($tanim['hizli'] as [$tur, $ad]) $h .= '<button type="button" class="chipbtn" data-tur="' . $tur . '" data-ad="' . e($ad) . '">+ ' . e($ad) . '</button>';
    $h .= '</div><div class="kalemler"><div class="krow khead"><span>Tür</span><span>Ürün / hizmet</span><span>Birim</span><span>Miktar</span><span>Birim fiyat (KDV hariç)</span><span>KDV</span><span>Tutar (KDV dahil)</span><span></span></div><div id="kbody">';
    foreach ($kalemler ?: [['tur' => 'iscilik', 'aciklama' => '', 'adet' => 1, 'birim_fiyat' => '', 'kdv' => $kdvVars]] as $k) $h .= $satir($k);
    $h .= '</div><button type="button" class="btn btn-soft" id="kekle">+ Satır ekle</button></div>';
    $h .= '<div class="totals"><div><span>Ara toplam (KDV hariç)</span><b id="t_ara">0,00 ₺</b></div>'
        . '<div><span>İndirim (₺)</span><input class="inp num" name="iskonto" inputmode="decimal" value="' . ($iskonto > 0 ? $fmt($iskonto) : '') . '" placeholder="0,00"></div>'
        . '<div><span>KDV</span><b id="t_kdv">0,00 ₺</b></div>'
        . '<div class="grand"><span>Genel toplam</span><b id="t_top">0,00 ₺</b></div></div>';
    $h .= '<template id="ksablon">' . $satir(['tur' => 'iscilik', 'aciklama' => '', 'adet' => 1, 'birim_fiyat' => '', 'kdv' => $kdvVars]) . '</template>';
    return $h;
}

/** Kalem tablosunun canlı hesaplama betiği. Formun id'si "sform" olmalı. */
function kalem_js(): string
{
    return <<<'JS'
<script>
(function () {
  const body = document.getElementById('kbody'), sablon = document.getElementById('ksablon'), form = document.getElementById('sform');
  if (!body) return;
  const oku = s => { s = (s || '').replace(/[₺\s]/g, ''); if (s.includes(',')) s = s.replace(/\./g, '').replace(',', '.'); else if (/^\d{1,3}(\.\d{3})+$/.test(s)) s = s.replace(/\./g, ''); const n = parseFloat(s); return isNaN(n) ? 0 : n; };
  const yaz = n => n.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  window.kalemHesapla = function () {
    let ara = 0, kdv = 0;
    body.querySelectorAll('.krow').forEach(r => {
      const net = oku(r.querySelector('[name="k_adet[]"]').value) * oku(r.querySelector('[name="k_fiyat[]"]').value);
      const k = parseInt(r.querySelector('[name="k_kdv[]"]').value, 10) || 0;
      r.querySelector('.ktutar').textContent = yaz(net * (1 + k / 100)); ara += net; kdv += net * k / 100;
    });
    const isk = Math.min(ara, oku(form.iskonto.value)), oran = ara > 0 ? (ara - isk) / ara : 0, top = ara - isk + kdv * oran;
    document.getElementById('t_ara').textContent = yaz(ara) + ' ₺';
    document.getElementById('t_kdv').textContent = yaz(kdv * oran) + ' ₺';
    document.getElementById('t_top').textContent = yaz(top) + ' ₺';
    document.dispatchEvent(new CustomEvent('kalemtoplam', { detail: top }));
  };
  function ekle(tur, ad) {
    const bos = [...body.querySelectorAll('.krow')].find(r => !r.querySelector('[name="k_aciklama[]"]').value);
    const r = bos || sablon.content.firstElementChild.cloneNode(true);
    if (!bos) body.appendChild(r);
    if (tur) r.querySelector('[name="k_tur[]"]').value = tur;
    if (ad) r.querySelector('[name="k_aciklama[]"]').value = ad;
    r.querySelector(ad ? '[name="k_fiyat[]"]' : '[name="k_aciklama[]"]').focus();
    kalemHesapla();
  }
  document.getElementById('kekle').onclick = () => ekle();
  document.querySelectorAll('.chipbtn').forEach(b => b.onclick = () => ekle(b.dataset.tur, b.dataset.ad));
  body.addEventListener('click', e => { if (e.target.classList.contains('kdel')) { const rs = body.querySelectorAll('.krow'); if (rs.length > 1) e.target.closest('.krow').remove(); else rs[0].querySelectorAll('input').forEach(i => i.value = i.name === 'k_adet[]' ? '1' : ''); kalemHesapla(); } });
  form.addEventListener('input', kalemHesapla); form.addEventListener('change', kalemHesapla); kalemHesapla();
})();
</script>
JS;
}

/** Üstten görünüşlü araç hasar şeması (tıklanabilir). $hasar: parça => tür. $salt: sadece göster. */
function hasar_semasi(array $hasar, bool $salt = false): string
{
    // Parça yolları: araç yukarıdan görünüş, ön taraf solda
    $P = [
        'on_tampon' => 'M18,62 Q8,62 8,80 L8,140 Q8,158 18,158 L34,158 L34,62 Z',
        'kaput' => 'M38,64 L108,58 L108,162 L38,156 Z',
        'tavan' => 'M150,66 L262,66 Q272,110 262,154 L150,154 Q140,110 150,66 Z',
        'bagaj' => 'M300,60 L344,64 L344,156 L300,160 Z',
        'arka_tampon' => 'M348,62 L362,62 Q372,62 372,80 L372,140 Q372,158 362,158 L348,158 Z',
        'sol_on_camurluk' => 'M38,30 Q70,20 108,22 L108,54 L38,60 Z',
        'sol_on_kapi' => 'M112,22 L200,22 L200,62 L112,58 Z',
        'sol_arka_kapi' => 'M204,22 L276,22 L276,58 L204,62 Z',
        'sol_arka_camurluk' => 'M280,22 Q330,22 344,32 L344,60 L280,56 Z',
        'sag_on_camurluk' => 'M38,160 L108,166 L108,198 Q70,200 38,190 Z',
        'sag_on_kapi' => 'M112,162 L200,158 L200,198 L112,198 Z',
        'sag_arka_kapi' => 'M204,158 L276,162 L276,198 L204,198 Z',
        'sag_arka_camurluk' => 'M280,164 L344,160 L344,188 Q330,198 280,198 Z',
    ];
    $svg = '<svg class="hasar' . ($salt ? ' salt' : '') . '" viewBox="0 0 380 220" xmlns="http://www.w3.org/2000/svg">'
        . '<rect x="110" y="62" width="36" height="96" rx="8" class="cam"/><rect x="266" y="64" width="30" height="92" rx="8" class="cam"/>';
    foreach ($P as $k => $d) {
        $t = $hasar[$k] ?? '';
        $renk = HASAR_TURLERI[$t][1] ?? '';
        $svg .= '<path data-p="' . $k . '" data-h="' . e($t) . '" d="' . $d . '"' . ($renk ? ' style="fill:' . $renk . '"' : '') . '><title>' . e(EKSPERTIZ_PARCALAR[$k]) . '</title></path>';
    }
    $svg .= '<text x="190" y="214" text-anchor="middle">Ön ◀ · ▶ Arka</text></svg>';
    if ($salt) return $svg;
    $leg = '';
    foreach (HASAR_TURLERI as $k => [$ad, $r]) $leg .= '<span><i style="background:' . $r . '"></i>' . e($ad) . '</span>';
    return '<div class="hasarkutu">' . $svg . '<div class="hasarleg">' . $leg . '<small class="muted">Parçaya her tıklayışta hasar türü değişir; dördüncü tıklama işareti kaldırır.</small></div>'
        . '<input type="hidden" name="ekspertiz" id="ekspertiz" value="' . e(json_encode((object)$hasar, JSON_UNESCAPED_UNICODE)) . '">'
        . '<div class="hasarliste" id="hasarliste"></div></div>'
        . '<script>(function(){const T=' . json_encode(array_map(fn($x) => [$x[0], $x[1]], HASAR_TURLERI), JSON_UNESCAPED_UNICODE) . ',A=' . json_encode(EKSPERTIZ_PARCALAR, JSON_UNESCAPED_UNICODE) . ',S=["","cizik","gocuk","kirik"],inp=document.getElementById("ekspertiz"),lst=document.getElementById("hasarliste");'
        . 'function yaz(){const o={};document.querySelectorAll("svg.hasar path[data-p]").forEach(p=>{if(p.dataset.h)o[p.dataset.p]=p.dataset.h});inp.value=JSON.stringify(o);lst.innerHTML=Object.entries(o).map(([k,v])=>"<span style=\\"border-color:"+T[v][1]+"\\">"+A[k]+": <b>"+T[v][0]+"</b></span>").join("")||"<small class=\\"muted\\">Hasar işaretlenmedi.</small>";inp.dispatchEvent(new Event("change",{bubbles:true}))}'
        . 'document.querySelectorAll("svg.hasar path[data-p]").forEach(p=>p.addEventListener("click",()=>{const n=S[(S.indexOf(p.dataset.h)+1)%S.length];p.dataset.h=n;p.style.fill=n?T[n][1]:"";yaz()}));yaz();})();</script>';
}

/** Yakıt göstergesi (0-100). */
function yakit_gostergesi(?int $yuzde): string
{
    $y = max(0, min(100, (int)$yuzde));
    $aci = -120 + $y * 2.4; // -120 (boş) .. +120 (dolu)
    $cizgi = '';
    for ($i = 0; $i <= 8; $i++) {
        $a = deg2rad(-120 + $i * 30 - 90);
        $cizgi .= sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" class="%s"/>', 60 + 40 * cos($a), 62 + 40 * sin($a), 60 + 50 * cos($a), 62 + 50 * sin($a), $i < 2 ? 'az' : '');
    }
    return '<svg class="yakit" viewBox="0 0 120 100"><g>' . $cizgi . '</g><text x="14" y="98">E</text><text x="100" y="98">F</text>'
        . '<line x1="60" y1="62" x2="60" y2="20" class="ibre" transform="rotate(' . $aci . ' 60 62)"/><circle cx="60" cy="62" r="6" class="merkez"/>'
        . '<text x="60" y="88" text-anchor="middle" class="yuzde">%' . $y . '</text></svg>';
}

/** Parmakla/mouse ile imza alanı. Kayıtlı imza varsa gösterilir. */
function imza_alani(string $ad, string $etiket, ?string $mevcut): string
{
    return '<div class="imza"><span>' . e($etiket) . '</span>'
        . '<canvas data-ad="' . e($ad) . '" width="600" height="220"></canvas>'
        . ($mevcut ? '<img class="imza-eski" src="' . e($mevcut) . '" alt="">' : '')
        . '<input type="hidden" name="' . e($ad) . '" value="">'
        . '<button type="button" class="btn btn-ghost imza-temizle">Temizle</button></div>';
}

function imza_js(): string
{
    return <<<'JS'
<script>
document.querySelectorAll('.imza canvas').forEach(cv => {
  const ctx = cv.getContext('2d'), inp = cv.parentNode.querySelector('input'), eski = cv.parentNode.querySelector('.imza-eski');
  ctx.lineWidth = 3.2; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#22074F';
  let ciz = false, bos = true;
  const nokta = e => { const r = cv.getBoundingClientRect(), t = e.touches ? e.touches[0] : e; return [(t.clientX - r.left) * cv.width / r.width, (t.clientY - r.top) * cv.height / r.height]; };
  const bas = e => { e.preventDefault(); ciz = true; if (eski) eski.style.display = 'none'; const [x, y] = nokta(e); ctx.beginPath(); ctx.moveTo(x, y); };
  const git = e => { if (!ciz) return; e.preventDefault(); const [x, y] = nokta(e); ctx.lineTo(x, y); ctx.stroke(); bos = false; };
  const bit = () => { if (ciz && !bos) inp.value = cv.toDataURL('image/png'); ciz = false; };
  cv.addEventListener('mousedown', bas); cv.addEventListener('mousemove', git); window.addEventListener('mouseup', bit);
  cv.addEventListener('touchstart', bas, { passive: false }); cv.addEventListener('touchmove', git, { passive: false }); cv.addEventListener('touchend', bit);
  cv.parentNode.querySelector('.imza-temizle').onclick = () => { ctx.clearRect(0, 0, cv.width, cv.height); inp.value = 'sil'; bos = true; if (eski) eski.style.display = 'none'; };
});
</script>
JS;
}

/** Gönderilen imza verisini doğrular: geçerli PNG data URL, 'sil' (boş) ya da null (değişmedi). */
function imza_oku(string $alan): string|false|null
{
    $v = (string)($_POST[$alan] ?? '');
    if ($v === '') return null;
    if ($v === 'sil') return false;
    if (strlen($v) > 400000 || !str_starts_with($v, 'data:image/png;base64,')) return null;
    $bin = base64_decode(substr($v, 22), true);
    if ($bin === false || substr($bin, 0, 8) !== "\x89PNG\r\n\x1a\n") return null;
    return $v;
}

/** QR kod (SVG). Veri bir bağlantıdır. */
function qr_kutu(string $veri, string $etiket, int $boy = 120): string
{
    return '<div class="qr" data-qr="' . e($veri) . '" data-boy="' . $boy . '"></div><small class="qrlbl">' . e($etiket) . '</small>';
}

function qr_js(): string
{
    return '<script src="' . asset('assets/vendor/qrcode.js') . '"></script><script>document.querySelectorAll(".qr[data-qr]").forEach(d=>{const q=qrcode(0,"M");q.addData(d.dataset.qr);q.make();d.innerHTML=q.createSvgTag({cellSize:3,margin:0,scalable:true});d.firstChild.style.width=d.dataset.boy+"px";d.firstChild.style.height=d.dataset.boy+"px"});</script>';
}

/** Personel seçim kutusu (danışman/teknisyen). Ustanın adı da eklenir. */
function personel_sec(string $ad, array $usta, string $gorev, ?string $secili): string
{
    $liste = array_column(rows('SELECT ad FROM personel WHERE usta_id = ? AND aktif = 1 AND (gorev = ? OR gorev = \'diger\') ORDER BY ad', [$usta['id'], $gorev]), 'ad');
    array_unshift($liste, $usta['ad_soyad']);
    if ($secili && !in_array($secili, $liste, true)) $liste[] = $secili;
    $h = '<select class="inp" name="' . e($ad) . '"><option value="">Seçiniz</option>';
    foreach (array_unique($liste) as $p) $h .= '<option' . ($p === $secili ? ' selected' : '') . '>' . e($p) . '</option>';
    return $h . '</select>';
}

/** Servis kaydının kart ve müşteri bilgisinden anlık kopyalarını günceller (raporlar bunları kullanır). */
function servis_kart_kopyala(int $sid, array $kart): void
{
    q('UPDATE servisler SET kart_id = ?, sektor = ?, anahtar = ?, baslik = ?, ozel = ? WHERE id = ?',
        [$kart['id'], $kart['sektor'], $kart['anahtar'], $kart['baslik'], $kart['ozel'], $sid]);
}

function servis_musteri_kopyala(int $sid, array $m): void
{
    q('UPDATE servisler SET musteri_id = ?, musteri_ad = ?, musteri_tel = ?, musteri_firma = ? WHERE id = ?',
        [$m['id'], $m['ad_soyad'], $m['telefon'], $m['tip'] === 'kurumsal' ? ($m['unvan'] ?: null) : null, $sid]);
}

/** Usta + kabul taslağı oturumu (yeni kayıt için kart/müşteri seçimi). */
function kabul_taslak(): array
{
    return $_SESSION['kabul'] ?? ['kart_id' => null, 'musteri_id' => null];
}

const ILLER = ['Adana', 'Adıyaman', 'Afyonkarahisar', 'Ağrı', 'Aksaray', 'Amasya', 'Ankara', 'Antalya', 'Ardahan', 'Artvin', 'Aydın', 'Balıkesir', 'Bartın', 'Batman',
    'Bayburt', 'Bilecik', 'Bingöl', 'Bitlis', 'Bolu', 'Burdur', 'Bursa', 'Çanakkale', 'Çankırı', 'Çorum', 'Denizli', 'Diyarbakır', 'Düzce', 'Edirne', 'Elazığ',
    'Erzincan', 'Erzurum', 'Eskişehir', 'Gaziantep', 'Giresun', 'Gümüşhane', 'Hakkari', 'Hatay', 'Iğdır', 'Isparta', 'İstanbul', 'İzmir', 'Kahramanmaraş',
    'Karabük', 'Karaman', 'Kars', 'Kastamonu', 'Kayseri', 'Kilis', 'Kırıkkale', 'Kırklareli', 'Kırşehir', 'Kocaeli', 'Konya', 'Kütahya', 'Malatya', 'Manisa',
    'Mardin', 'Mersin', 'Muğla', 'Muş', 'Nevşehir', 'Niğde', 'Ordu', 'Osmaniye', 'Rize', 'Sakarya', 'Samsun', 'Şanlıurfa', 'Siirt', 'Sinop', 'Şırnak', 'Sivas',
    'Tekirdağ', 'Tokat', 'Trabzon', 'Tunceli', 'Uşak', 'Van', 'Yalova', 'Yozgat', 'Zonguldak'];

/** Müşteri formunu okur ve doğrular: [degerler, hata] */
function musteri_oku(): array
{
    $tip = post('tip') === 'kurumsal' ? 'kurumsal' : 'bireysel';
    $v = [
        'tip' => $tip,
        'ad_soyad' => mb_substr(preg_replace('/\s+/u', ' ', post('ad_soyad')), 0, 100),
        'unvan' => mb_substr(post('unvan'), 0, 150) ?: null,
        'tc_vkn' => preg_replace('/\D/', '', post('tc_vkn')) ?: null,
        'vergi_dairesi' => mb_substr(post('vergi_dairesi'), 0, 80) ?: null,
        'telefon' => post('telefon') !== '' ? tel_temizle(post('telefon')) : null,
        'eposta' => mb_strtolower(mb_substr(post('eposta'), 0, 150)) ?: null,
        'adres' => mb_substr(post('adres'), 0, 255) ?: null,
        'sehir' => in_array(post('sehir'), ILLER, true) ? post('sehir') : null,
        'etiket' => mb_substr(post('etiket'), 0, 60) ?: null,
        'aciklama' => mb_substr(post('aciklama'), 0, 2000) ?: null,
    ];
    $hata = '';
    if ($tip === 'kurumsal' && !$v['unvan']) $hata = 'Kurumsal müşteri için ticari ünvanı yazın.';
    elseif (mb_strlen($v['ad_soyad']) < 2) $hata = $tip === 'kurumsal' ? 'Yetkili kişinin adını yazın.' : 'Müşterinin adını ve soyadını yazın.';
    elseif ($v['telefon'] && !tel_gecerli($v['telefon'])) $hata = 'Telefon numarası geçersiz (05xx xxx xx xx).';
    elseif ($v['tc_vkn'] && !in_array(strlen($v['tc_vkn']), [10, 11], true)) $hata = 'TC kimlik no 11, vergi no 10 haneli olmalı.';
    elseif ($v['eposta'] && !filter_var($v['eposta'], FILTER_VALIDATE_EMAIL)) $hata = 'E-posta adresi geçersiz.';
    return [$v, $hata];
}

function musteri_kaydet(int $ustaId, array $v, ?int $id): int
{
    if ($id) {
        q('UPDATE musteriler SET tip=?, ad_soyad=?, unvan=?, tc_vkn=?, vergi_dairesi=?, telefon=?, eposta=?, adres=?, sehir=?, etiket=?, aciklama=? WHERE id=? AND usta_id=?',
            [...array_values($v), $id, $ustaId]);
        return $id;
    }
    q('INSERT INTO musteriler (tip, ad_soyad, unvan, tc_vkn, vergi_dairesi, telefon, eposta, adres, sehir, etiket, aciklama, usta_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
        [...array_values($v), $ustaId]);
    return (int)db()->lastInsertId();
}

/** Müşteri form alanları. $m: mevcut kayıt ya da gönderilen değerler. */
function musteri_formu(?array $m): string
{
    $v = fn($k) => e((string)($m[$k] ?? ''));
    $tip = $m['tip'] ?? 'bireysel';
    $h = '<div class="fgrid">'
        . '<div class="frow wide"><span>Müşteri tipi</span><span class="seg" style="margin:0;max-width:360px">'
        . '<label><input type="radio" name="tip" value="bireysel"' . ($tip === 'bireysel' ? ' checked' : '') . '><span>👤 Bireysel</span></label>'
        . '<label><input type="radio" name="tip" value="kurumsal"' . ($tip === 'kurumsal' ? ' checked' : '') . '><span>🏢 Kurumsal</span></label></span></div>'
        . '<label class="frow kurumsal-alan"><span>Ticari ünvan</span><input class="inp" name="unvan" maxlength="150" value="' . $v('unvan') . '" placeholder="ÖRN: ABC NAKLİYAT LTD. ŞTİ."></label>'
        . '<label class="frow"><span>Ad soyad *</span><input class="inp" name="ad_soyad" required maxlength="100" value="' . $v('ad_soyad') . '"></label>'
        . '<label class="frow"><span>Telefon</span><input class="inp" name="telefon" type="tel" inputmode="tel" placeholder="05xx xxx xx xx" value="' . (!empty($m['telefon']) && tel_gecerli((string)$m['telefon']) ? e(tel_goster($m['telefon'])) : $v('telefon')) . '"></label>'
        . '<label class="frow"><span>TC / Vergi no</span><input class="inp" name="tc_vkn" inputmode="numeric" maxlength="11" value="' . $v('tc_vkn') . '"></label>'
        . '<label class="frow"><span>Vergi dairesi</span><input class="inp" name="vergi_dairesi" maxlength="80" value="' . $v('vergi_dairesi') . '"></label>'
        . '<label class="frow"><span>E-posta</span><input class="inp" name="eposta" type="email" maxlength="150" value="' . $v('eposta') . '"></label>'
        . '<label class="frow"><span>Şehir</span><select class="inp" name="sehir"><option value="">Seçiniz</option>';
    $sehir = $m['sehir'] ?? 'Samsun';
    foreach (ILLER as $il) $h .= '<option' . ($il === $sehir ? ' selected' : '') . '>' . e($il) . '</option>';
    $h .= '</select></label>'
        . '<label class="frow wide"><span>Adres</span><input class="inp" name="adres" maxlength="255" value="' . $v('adres') . '"></label>'
        . '<label class="frow"><span>Etiket</span><input class="inp" name="etiket" maxlength="60" list="dl-etiket" value="' . $v('etiket') . '" placeholder="Örn: Filo, VIP, Sanayi"></label>'
        . '<label class="frow wide"><span>Açıklama</span><textarea class="inp" name="aciklama" rows="2">' . $v('aciklama') . '</textarea></label></div>'
        . '<script>(function(){const f=document.querySelectorAll(".kurumsal-alan");function g(){const k=document.querySelector("input[name=tip]:checked").value==="kurumsal";f.forEach(x=>x.style.display=k?"":"none")}document.querySelectorAll("input[name=tip]").forEach(r=>r.addEventListener("change",g));g()})();</script>';
    return $h;
}

/** Müşteri bakiyesi: servis toplamları − tahsilatlar (iptal hariç). Pozitif = müşterinin borcu. */
function musteri_bakiye(int $musteriId): float
{
    $t = servis_tutar_sql(); $o = servis_odenen_sql();
    return (float)val("SELECT COALESCE(SUM($t - $o), 0) FROM servisler s WHERE s.musteri_id = ? AND s.durum <> 'iptal'", [$musteriId]);
}

function etiket_datalist(int $ustaId): string
{
    $et = array_unique(array_filter(array_merge(
        array_column(rows('SELECT DISTINCT etiket FROM musteriler WHERE usta_id = ? AND etiket IS NOT NULL', [$ustaId]), 'etiket'),
        array_column(rows('SELECT DISTINCT etiket FROM servisler WHERE usta_id = ? AND etiket IS NOT NULL', [$ustaId]), 'etiket')
    )));
    return '<datalist id="dl-etiket">' . implode('', array_map(fn($x) => '<option value="' . e($x) . '">', $et)) . '</datalist>';
}

function kabul_metni_varsayilan(array $tanim): string
{
    $k = tr_lower($tanim['kayit']);
    return "Yukarıda belirtilen işlemler ve tutarlar tahminidir. Ek işlem gerekirse müşteriden onay alınır. "
        . "Bu formu imzalayarak $k için servise başvurduğumu, belirtilen işlemlerin yapılması için servise yetki verdiğimi ve genel servis şartlarını kabul ettiğimi beyan ederim. "
        . "Ödeme, iş tamamlandığında yapılacaktır.";
}

function teslim_metni_varsayilan(array $tanim): string
{
    $k = tr_lower($tanim['kayit']);
    return "Yukarıda belirtilen işlemleri yapılmış olarak $k teslim aldım. Değişen parçalar tarafıma gösterilmiş / teslim edilmiştir. "
        . "Garanti, belirtilen süre ve kapsamla sınırlıdır.";
}

/** Kabul/teslim formlarının ortak üst kısmı ve bilgi blokları. */
function belge_ust(array $s, array $u, ?array $kart, ?array $m, string $baslik): string
{
    $tanim = sektorler()[$s['sektor']] ?? sektorler()['yerinde'];
    $eposta = (string)val('SELECT eposta FROM kullanicilar WHERE id = ?', [$u['kullanici_id']]);
    $firma = '<div class="firma">' . ($u['firma_logo'] ? '<img src="' . e(upload_url($u['firma_logo'])) . '" alt="">' : '')
        . '<b>' . e($u['firma_unvan'] ?: $u['ad_soyad']) . '</b>' . e($u['meslek']) . '<br>📞 ' . e(tel_goster($u['telefon']))
        . ($eposta ? '<br>' . e($eposta) : '') . ($u['adres'] ? '<br>' . e($u['adres']) : '')
        . ($u['vergi_no'] ? '<br>' . e(trim($u['vergi_dairesi'] . ' V.D. · ' . $u['vergi_no'], ' ·')) : '') . '</div>';
    $h = '<div class="belgebas"><div>' . qr_kutu(rapor_url($s), 'Dijital iş emri') . '</div>'
        . '<div style="text-align:center"><h2>' . e($baslik) . '</h2><div style="margin-top:10px">' . ($kart ? kart_rozet($kart, 'b') : '') . '</div>'
        . '<div style="margin-top:6px;font-weight:700">' . e($m['unvan'] ?? '' ?: $s['musteri_ad']) . '</div></div>' . $firma . '</div>';
    $h .= '<div class="bilgiserit"><div><span>Servise giriş</span><b>' . date('d.m.Y H:i', strtotime($s['giris_tarihi'])) . '</b></div>'
        . '<div><span>Servis kayıt no</span><b>#' . (int)$s['no'] . '</b></div>'
        . '<div><span>Servis danışmanı</span><b>' . e($s['danisman'] ?: '—') . '</b></div>'
        . '<div><span>Teknisyen</span><b>' . e($s['teknisyen'] ?: '—') . '</b></div></div>';
    // Müşteri
    $h .= '<div class="kutu"><h3>👤 Müşteri Bilgileri</h3><div class="ic"><div class="dl2">'
        . '<div><span>' . (($m['tip'] ?? '') === 'kurumsal' ? 'Ünvan' : 'Ad soyad') . '</span><b>' . e(($m['unvan'] ?? '') ?: $s['musteri_ad']) . '</b></div>'
        . ((($m['tip'] ?? '') === 'kurumsal') ? '<div><span>Yetkili</span><b>' . e($m['ad_soyad']) . '</b></div>' : '')
        . ($s['musteri_tel'] ? '<div><span>Telefon</span><b>' . e(tel_goster($s['musteri_tel'])) . '</b></div>' : '')
        . (!empty($m['tc_vkn']) ? '<div><span>TC / Vergi no</span><b>' . e($m['tc_vkn']) . '</b></div>' : '')
        . (!empty($m['adres']) ? '<div><span>Adres</span><b>' . e(trim($m['adres'] . ' ' . $m['sehir'])) . '</b></div>' : '')
        . ($m ? '<div><span>Müşteri kodu</span><b>' . (int)$m['id'] . '</b></div>' : '')
        . ($s['getiren'] ? '<div><span>Getiren</span><b>' . e($s['getiren']) . '</b></div>' : '')
        . '</div></div></div>';
    // Kart
    $ozel = json_decode((string)$s['ozel'], true) ?: [];
    $h .= '<div class="kutu"><h3>' . $tanim['ikon'] . ' ' . e($tanim['kayit']) . ' Bilgileri</h3><div class="ic" style="display:grid;grid-template-columns:auto 1fr auto;gap:18px;align-items:center">'
        . '<div>' . ($kart ? qr_kutu(url_tam('usta/kart.php?id=' . $kart['id']), 'Geçmiş', 100) : '') . '</div><div class="dl2">';
    foreach ($tanim['alanlar'] as $a) {
        $v = trim((string)($ozel[$a[0]] ?? ''));
        if ($v === '' || $a[2] === 'textarea') continue;
        $h .= '<div><span>' . e($a[1]) . '</span><b>' . e($a[2] === 'date' ? date('d.m.Y', strtotime($v)) : $v) . '</b></div>';
    }
    if (!empty($tanim['servis']['km']) && $s['km'] !== null) $h .= '<div><span>' . e($tanim['servis']['km']) . '</span><b>' . sayi($s['km']) . '</b></div>';
    $h .= '</div><div>' . (!empty($tanim['servis']['yakit']) && $s['yakit'] !== null ? '<div style="text-align:center;font-weight:700;font-size:12px">Depo durumu</div>' . yakit_gostergesi((int)$s['yakit']) : '') . '</div></div></div>';
    $hasar = json_decode((string)$s['ekspertiz'], true) ?: [];
    if (!empty($tanim['servis']['ekspertiz']) && $hasar) {
        $h .= '<div class="kutu"><h3>🔍 Ekspertiz / Hasar</h3><div class="ic" style="display:grid;grid-template-columns:minmax(0,420px) 1fr;gap:18px;align-items:center">' . hasar_semasi($hasar, true) . '<div class="hasarliste">';
        foreach ($hasar as $p => $t) $h .= '<span style="border-color:' . HASAR_TURLERI[$t][1] . '">' . e(EKSPERTIZ_PARCALAR[$p] ?? $p) . ': <b>' . e(HASAR_TURLERI[$t][0]) . '</b></span>';
        $h .= '</div></div></div>';
    }
    $sg = json_decode((string)$s['sigorta'], true) ?: [];
    if ($sg) {
        $h .= '<div class="kutu"><h3>🛡️ Sigorta Hasar Dosyası</h3><div class="ic"><div class="dl2">'
            . (!empty($sg['sirket']) ? '<div><span>Şirket</span><b>' . e($sg['sirket']) . '</b></div>' : '')
            . (!empty($sg['dosya']) ? '<div><span>Dosya no</span><b>' . e($sg['dosya']) . '</b></div>' : '')
            . (!empty($sg['eksper']) ? '<div><span>Eksper</span><b>' . e($sg['eksper']) . '</b></div>' : '')
            . (!empty($sg['tarih']) ? '<div><span>Hasar tarihi</span><b>' . date('d.m.Y', strtotime($sg['tarih'])) . '</b></div>' : '')
            . '</div></div></div>';
    }
    return $h;
}

/** Kalem tablosu (salt okunur) + toplamlar. */
function belge_kalemler(array $s, array $kalemler, array $odemeler = [], bool $odemeGoster = false): string
{
    $h = servis_hesap($s, $kalemler, $odemeler);
    $t = '<div class="scroll"><table class="tbl dar"><tr><th>Ürün / hizmet</th><th>Birim</th><th class="r">Birim fiyat (KDV hariç)</th><th class="r">KDV</th><th class="r">Birim fiyat (KDV dahil)</th><th class="r">Miktar</th><th class="r">Tutar (KDV dahil)</th></tr>';
    if (!$kalemler) $t .= '<tr><td colspan="7" class="muted">Henüz kalem eklenmedi.</td></tr>';
    foreach ($kalemler as $k) {
        $kdv = (int)($k['kdv'] ?? $s['kdv']);
        $dahil = (float)$k['birim_fiyat'] * (1 + $kdv / 100);
        $t .= '<tr><td>' . e($k['aciklama']) . '</td><td>' . e($k['birim'] ?? 'Adet') . '</td><td class="r">' . para($k['birim_fiyat']) . '</td><td class="r">%' . $kdv . '</td>'
            . '<td class="r">' . para($dahil) . '</td><td class="r">' . rtrim(rtrim(number_format((float)$k['adet'], 2, ',', '.'), '0'), ',') . '</td><td class="r"><b>' . para($dahil * (float)$k['adet']) . '</b></td></tr>';
    }
    $t .= '</table></div><div class="totals" style="margin-top:12px"><div><span>Toplam (KDV hariç)</span><b>' . para($h['ara']) . '</b></div>'
        . ($h['iskonto'] > 0 ? '<div><span>İndirim</span><b>−' . para($h['iskonto']) . '</b></div>' : '')
        . '<div><span>Toplam KDV</span><b>' . para($h['kdv']) . '</b></div><div class="grand"><span>Genel toplam</span><b>' . para($h['toplam']) . '</b></div>'
        . ($odemeGoster ? '<div><span>Ödenen</span><b>' . para($h['odenen']) . '</b></div><div><span>' . ($h['kalan'] > 0.009 ? 'Kalan' : 'Durum') . '</span><b class="' . ($h['kalan'] > 0.009 ? 'down' : 'up') . '">' . ($h['kalan'] > 0.009 ? para($h['kalan']) : 'Ödendi ✓') . '</b></div>' : '')
        . '</div>';
    return $t;
}

/** Tam adres (QR için). */
function url_tam(string $yol): string
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    return ($host ? ($https ? 'https://' : 'http://') . $host : '') . url($yol);
}

/** Karttaki tarih alanlarından yaklaşan/geçmiş hatırlatmalar (30 gün). */
function kart_hatirlatmalar(array $kart): array
{
    $tanim = sektorler()[$kart['sektor']] ?? null;
    if (!$tanim) return [];
    $ozel = json_decode((string)$kart['ozel'], true) ?: [];
    $out = [];
    foreach ($tanim['alanlar'] as $a) {
        if ($a[2] !== 'date' || empty($ozel[$a[0]]) || !in_array($a[0], ['muayene', 'trafik', 'kasko', 'takograf', 'garanti_bitis'], true)) continue;
        $gun = (int)floor((strtotime($ozel[$a[0]]) - strtotime(date('Y-m-d'))) / 86400);
        if ($gun <= 30) $out[] = [str_replace(' bitiş', '', $a[1]), $gun];
    }
    return $out;
}

function hatirlatma_rozet(array $h): string
{
    return implode(' ', array_map(fn($x) => '<span class="hatirlat' . ($x[1] >= 0 ? ' y' : '') . '">' . e($x[0]) . ': ' . ($x[1] < 0 ? abs($x[1]) . ' gün geçti' : ($x[1] === 0 ? 'bugün' : $x[1] . ' gün kaldı')) . '</span>', $h));
}
