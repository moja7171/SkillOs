#!/usr/bin/env python3
"""Convert .srt subtitles next to the videos into .vtt (what <track> needs).

    python3 tools/authoring/srt2vtt.py course/python-deep-dive-1          # every *.srt → *.vtt (skips existing)
    python3 tools/authoring/srt2vtt.py course/x --force

Drops cues that are only a watermark/credit line and normalizes "00:00:01,000" timestamps.
"""
import argparse
import os
import re

WATERMARK = re.compile(r'(subtitle|زیرنویس|translated by|ترجمه)', re.I)


def srt_to_vtt(src):
    text = open(src, encoding='utf-8-sig', errors='replace').read().replace('\r\n', '\n')
    blocks = re.split(r'\n\s*\n', text.strip())
    out = ['WEBVTT', '']
    for block in blocks:
        lines = [l for l in block.split('\n') if l.strip()]
        if not lines:
            continue
        if re.match(r'^\d+$', lines[0]):
            lines = lines[1:]
        if not lines or '-->' not in lines[0]:
            continue
        timing = lines[0].replace(',', '.')
        body = lines[1:]
        if not body or (len(body) == 1 and WATERMARK.search(body[0]) and len(body[0]) < 60):
            continue
        out.append(timing)
        out.extend(body)
        out.append('')
    return '\n'.join(out)


def main():
    p = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument('media_dir')
    p.add_argument('--force', action='store_true')
    a = p.parse_args()
    n = 0
    for f in sorted(os.listdir(a.media_dir)):
        if not f.endswith('.srt'):
            continue
        dst = os.path.join(a.media_dir, f[:-4] + '.vtt')
        if os.path.exists(dst) and not a.force:
            continue
        with open(dst, 'w', encoding='utf-8') as out:
            out.write(srt_to_vtt(os.path.join(a.media_dir, f)))
        n += 1
    print(f'{n} files converted')


if __name__ == '__main__':
    main()
