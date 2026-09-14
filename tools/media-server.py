#!/usr/bin/env python3
"""Serve the local course media folder to the browser with HTTP Range + CORS.

The deployed SkillOS (on shared hosting) renders video/subtitle URLs pointing at
MEDIA_BASE_URL — this server, running on the owner's own machine — so the videos
never leave the machine. The browser treats http://localhost as a secure origin,
so an https site can load media from it without mixed-content blocking.

    python3 tools/media-server.py                 # serves ./course on http://localhost:8765
    python3 tools/media-server.py --port 9000 --root /path/to/course

Then set MEDIA_BASE_URL=http://localhost:8765 in the server's .env (and hit
/_ops/optimize if config is cached).
"""
import argparse
import os
import sys
from functools import partial
from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer

CHUNK = 1024 * 256


class RangeHandler(SimpleHTTPRequestHandler):
    extensions_map = {
        **SimpleHTTPRequestHandler.extensions_map,
        '.mp4': 'video/mp4', '.webm': 'video/webm', '.m4v': 'video/mp4',
        '.vtt': 'text/vtt; charset=utf-8', '.srt': 'text/plain; charset=utf-8',
        '.pdf': 'application/pdf', '.ipynb': 'application/json', '.py': 'text/plain; charset=utf-8',
    }

    def end_headers(self):
        # <track> and <video crossorigin> need CORS; Range must be exposed for seeking.
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Headers', 'Range')
        self.send_header('Access-Control-Expose-Headers', 'Content-Range, Content-Length, Accept-Ranges')
        self.send_header('Accept-Ranges', 'bytes')
        self.send_header('Cache-Control', 'public, max-age=3600')
        super().end_headers()

    def do_OPTIONS(self):
        self.send_response(204)
        self.end_headers()

    def send_head(self):
        path = self.translate_path(self.path)
        if os.path.isdir(path) or not os.path.exists(path):
            return super().send_head()  # directory listing / 404 as usual

        size = os.path.getsize(path)
        ctype = self.guess_type(path)
        rng = self.headers.get('Range')
        start, end = 0, size - 1
        if rng and rng.startswith('bytes='):
            first, _, last = rng[6:].partition('-')
            try:
                if first:
                    start = int(first)
                    end = int(last) if last else size - 1
                else:  # suffix range: bytes=-500
                    start = max(0, size - int(last))
            except ValueError:
                start, end = 0, size - 1
            if start > end or start >= size:
                self.send_response(416)
                self.send_header('Content-Range', f'bytes */{size}')
                self.end_headers()
                return None
            end = min(end, size - 1)
            self.send_response(206)
            self.send_header('Content-Range', f'bytes {start}-{end}/{size}')
        else:
            self.send_response(200)
        self.send_header('Content-Type', ctype)
        self.send_header('Content-Length', str(end - start + 1))
        self.send_header('Last-Modified', self.date_time_string(int(os.path.getmtime(path))))
        self.end_headers()
        f = open(path, 'rb')
        f.seek(start)
        self._range_end = end
        return f

    def copyfile(self, source, outputfile):
        end = getattr(self, '_range_end', None)
        if end is None:
            return super().copyfile(source, outputfile)
        remaining = end - source.tell() + 1
        while remaining > 0:
            buf = source.read(min(CHUNK, remaining))
            if not buf:
                break
            outputfile.write(buf)
            remaining -= len(buf)

    def log_message(self, fmt, *args):
        sys.stderr.write('%s %s\n' % (self.address_string(), fmt % args))


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument('--root', default=os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'course'))
    parser.add_argument('--port', type=int, default=8765)
    parser.add_argument('--bind', default='127.0.0.1', help='use 0.0.0.0 to allow other devices on your LAN')
    args = parser.parse_args()
    if not os.path.isdir(args.root):
        sys.exit(f'media root not found: {args.root}')
    handler = partial(RangeHandler, directory=args.root)
    server = ThreadingHTTPServer((args.bind, args.port), handler)
    print(f'Serving {args.root} at http://localhost:{args.port}/  (MEDIA_BASE_URL=http://localhost:{args.port})')
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        pass


if __name__ == '__main__':
    main()
