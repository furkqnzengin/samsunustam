import sys, os, json, importlib
mod, outdir, W, H = sys.argv[1], sys.argv[2], int(sys.argv[3]), int(sys.argv[4])
m = importlib.import_module(mod)
os.makedirs(outdir, exist_ok=True)
items = []
for ad, html in m.P:
    p = os.path.join(os.getcwd(), f'_{ad}.html'); open(p, 'w').write(html); items.append([ad, p])
json.dump({'items': items, 'out': outdir, 'W': W, 'H': H}, open('_job.json', 'w'))
