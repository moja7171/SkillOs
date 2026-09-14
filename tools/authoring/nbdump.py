#!/usr/bin/env python3
"""Print a Jupyter notebook as readable text (markdown, code, and outputs) for lesson writing.

    python3 tools/authoring/nbdump.py course/python-deep-dive-1/files/110-*.ipynb
"""
import json
import sys

for f in sys.argv[1:]:
    nb = json.load(open(f, encoding='utf-8'))
    print('=====', f)
    for c in nb.get('cells', []):
        src = ''.join(c.get('source', [])).strip()
        if not src:
            continue
        print('#', c['cell_type'])
        print(src)
        for o in c.get('outputs', []):
            t = ''.join(o.get('text', '')) if 'text' in o else ''.join(o.get('data', {}).get('text/plain', ''))
            if t.strip():
                print('>>', t.strip()[:600])
