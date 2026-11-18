# Sosyal medya tasarım kaynakları

Samsun Ustam'ın 20 post, 20 story ve 3 reels videosunun kaynak dosyaları. Görseller koddan üretilir; metinleri değiştirip yeniden çalıştırmak yeterli.

- `kit.py`: renkler, maskot, ortak sayfa iskeleti
- `postlar.py`, `storyler.py`: içerikler (1080×1080 ve 1080×1920)
- `reels.py` + `reel-ortak.js`: video sahneleri ve zaman çizelgesi
- `ses.py`: müzik ve ses efektleri (numpy ile sentez, telifsiz)

Üretim (Python 3, Node + Playwright, ffmpeg gerekir; `../assets/marka/*.svg` ve yerel font dosyaları bu klasöre kopyalanmalı):

```bash
python3 render.py postlar postlar 1080 1080 && node render.js
python3 render.py storyler storyler 1080 1920 && node render.js
python3 -c "import reels; [open(f'_{a}.html','w').write(h) for a,h,s in reels.R]"
node reel-render.js $PWD/_reel-1-evde-bir-sey-mi-bozuldu.html kare/r1 30
python3 ses.py kare/r1/cues.json ses-1.wav parlak
ffmpeg -framerate 30 -i kare/r1/%05d.jpg -i ses-1.wav -c:v libx264 -pix_fmt yuv420p -c:a aac -shortest reel-1.mp4
```
