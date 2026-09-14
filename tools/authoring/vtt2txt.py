#!/usr/bin/env python3
"""Turn subtitle files into plain transcripts for lesson writing.

    python3 tools/authoring/vtt2txt.py course/python-deep-dive-1 out/transcripts        # all *.en.vtt
    python3 tools/authoring/vtt2txt.py course/x out/transcripts --lang fa               # Persian instead
    python3 tools/authoring/vtt2txt.py course/x out/transcripts --only 102 103          # by lecture number prefix

Empty transcripts (subtitle files that only contain a watermark) are reported and skipped.
"""
import argparse
import os
import re
import sys

TIMING = re.compile(r'^\d+$|^\d{2}:\d{2}[:\d.,]* --> ')


def vtt_to_text(path):
    lines = []
    seen = set()
    for raw in open(path, encoding='utf-8-sig', errors='replace'):
        line = raw.strip()
        if not line or line == 'WEBVTT' or TIMING.match(line) or line.startswith('NOTE'):
            continue
        line = re.sub(r'<[^>]+>', '', line)          # strip <c>…</c> styling
        if line in seen:                             # duplicated rolling cues
            continue
        seen.add(line)
        lines.append(line)
    return '\n'.join(lines)


def main():
    p = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument('media_dir')
    p.add_argument('out_dir')
    p.add_argument('--lang', default='en')
    p.add_argument('--only', nargs='*', default=[], help='lecture number prefixes, e.g. 102 103')
    a = p.parse_args()
    os.makedirs(a.out_dir, exist_ok=True)
    suffix = f'.{a.lang}.vtt'
    files = sorted(f for f in os.listdir(a.media_dir) if f.endswith(suffix))
    if a.only:
        files = [f for f in files if f.split('-', 1)[0] in a.only]
    empty = []
    for f in files:
        text = vtt_to_text(os.path.join(a.media_dir, f))
        if len(text) < 200:
            empty.append(f)
            continue
        with open(os.path.join(a.out_dir, f[:-len(suffix)] + '.txt'), 'w', encoding='utf-8') as out:
            out.write(text)
    print(f'{len(files) - len(empty)} transcripts written to {a.out_dir}')
    if empty:
        print('EMPTY (use slides/notebooks/other language for these):', ', '.join(empty), file=sys.stderr)


if __name__ == '__main__':
    main()
