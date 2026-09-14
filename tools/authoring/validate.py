#!/usr/bin/env python3
"""Validate a course's content files before `php artisan content:import`.

    python3 tools/authoring/validate.py python-deep-dive-1

Checks the things the importer rejects (2 hints, MCQ shape, required fields) plus the
house rules this project uses (rubric ↔ correct_option, no stub lessons, balanced code
fences, no Persian inside code blocks, key points present).
"""
import glob
import json
import os
import re
import sys

FORMS = {'mcq', 'short_answer', 'coding', 'explanation', 'scenario'}
DIFF = {'intro', 'core', 'stretch'}
REQUIRED = ['key', 'title', 'form', 'difficulty', 'estimated_minutes', 'prompt', 'expected_outcome', 'hints', 'rubric']
PERSIAN = re.compile(r'[؀-ۿ]')


def main():
    slug = sys.argv[1] if len(sys.argv) > 1 else 'python-deep-dive-1'
    root = os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__)))), 'content', slug)
    problems = []
    warnings = []
    course = json.load(open(os.path.join(root, 'course.json'), encoding='utf-8'))
    slugs = [l['slug'] for l in course['lessons']]
    if len(slugs) != len(set(slugs)):
        problems.append('duplicate lesson slugs in course.json')

    for l in course['lessons']:
        s = l['slug']
        md = os.path.join(root, 'lessons', f'{s}.md')
        pj = os.path.join(root, 'lessons', f'{s}.practices.json')
        if not os.path.exists(md):
            problems.append(f'{s}: missing {s}.md'); continue
        text = open(md, encoding='utf-8').read()
        if len(text) < 500:
            problems.append(f'{s}: lesson text is a stub ({len(text)} bytes)')
        if text.count('```') % 2:
            problems.append(f'{s}: unbalanced code fences')
        for block in re.findall(r'```[^\n]*\n(.*?)```', text, re.S):
            if PERSIAN.search(block):
                warnings.append(f'{s}: Persian text inside a code block (write code comments in English; mixed-script lines render badly)')
                break
        if not l.get('key_points'):
            problems.append(f'{s}: no key_points in course.json')
        if l['slug'] != 'course-overview' and not os.path.exists(pj):
            problems.append(f'{s}: missing practices file')
        if os.path.exists(pj):
            try:
                practices = json.load(open(pj, encoding='utf-8'))
            except json.JSONDecodeError as e:
                problems.append(f'{s}: practices JSON invalid: {e}'); continue
            keys = [p.get('key') for p in practices]
            if len(keys) != len(set(keys)):
                problems.append(f'{s}: duplicate practice keys')
            for p in practices:
                k = p.get('key', '?')
                for r in REQUIRED:
                    if r not in p:
                        problems.append(f'{s}/{k}: missing {r}')
                if p.get('form') not in FORMS:
                    problems.append(f'{s}/{k}: bad form {p.get("form")}')
                if p.get('difficulty') not in DIFF:
                    problems.append(f'{s}/{k}: bad difficulty')
                if len(p.get('hints', [])) != 2:
                    problems.append(f'{s}/{k}: needs exactly 2 hints')
                if not 1 <= int(p.get('estimated_minutes', 0)) <= 10:
                    problems.append(f'{s}/{k}: estimated_minutes out of 1–10')
                if p.get('form') == 'mcq':
                    opts = p.get('options', [])
                    if len(opts) != 4 or len(set(opts)) != 4:
                        problems.append(f'{s}/{k}: mcq needs 4 distinct options')
                    if not (isinstance(p.get('correct_option'), int) and 0 <= p['correct_option'] <= 3):
                        problems.append(f'{s}/{k}: correct_option must be 0–3')
                    m = re.search(r'option (\d)', p.get('rubric', ''))
                    if not m or int(m.group(1)) != p.get('correct_option'):
                        problems.append(f'{s}/{k}: rubric must say "Correct only if option {p.get("correct_option")} is selected."')
    extra = {os.path.basename(f)[:-3] for f in glob.glob(os.path.join(root, 'lessons', '*.md'))} - set(slugs)
    if extra:
        problems.append(f'lesson files not in course.json: {sorted(extra)}')

    for w in warnings:
        print('!', w)
    for p in problems:
        print('✗', p)
    print(f'{len(course["lessons"])} lessons checked — {len(warnings)} warning(s), {"OK" if not problems else str(len(problems)) + " problem(s)"}')
    sys.exit(1 if problems else 0)


if __name__ == '__main__':
    main()
