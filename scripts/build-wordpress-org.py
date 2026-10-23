"""Reproduzierbares Einreichungspaket aus einer festen Dateiliste bauen."""
from pathlib import Path
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED
from hashlib import sha256
import json, shutil, re

root = Path(__file__).resolve().parents[1]
config = json.loads((root / 'wordpress-org/package.json').read_text())
source = root / config['source']
slug = config['slug']
assert re.fullmatch(r'[a-z0-9]+(?:-[a-z0-9]+)*', slug)
stage = root / 'build/wordpress-org' / slug
output = root / 'artifacts/wordpress-org'
if stage.exists():
    shutil.rmtree(stage)
stage.mkdir(parents=True, exist_ok=True)
output.mkdir(parents=True, exist_ok=True)
allowed = {'.php', '.css', '.js', '.json', '.txt', '.md', '.svg', '.png', '.jpg', '.jpeg', '.gif', '.webp', '.woff', '.woff2', '.po', '.mo', '.pot'}
files = []
for entry in config['include']:
    path = source / entry
    assert path.exists(), path
    files.extend(path.rglob('*') if path.is_dir() else [path])
archive = output / (slug + '.zip')
with ZipFile(archive, 'w', ZIP_DEFLATED) as bundle:
    for path in sorted(files):
        if not path.is_file():
            continue
        relative = path.relative_to(source)
        assert not path.is_symlink() and not any(part.startswith('.') for part in relative.parts)
        assert path.suffix.lower() in allowed or path.name == 'LICENSE', path
        data = path.read_bytes()
        target = stage / relative
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_bytes(data)
        info = ZipInfo(slug + '/' + relative.as_posix(), date_time=(2026, 1, 1, 0, 0, 0))
        info.compress_type = ZIP_DEFLATED
        info.external_attr = 0o100644 << 16
        bundle.writestr(info, data)
with ZipFile(archive) as bundle:
    assert bundle.testzip() is None
    assert slug + '/readme.txt' in bundle.namelist()
    assert all(name.startswith(slug + '/') and '..' not in Path(name).parts for name in bundle.namelist())
Path(str(archive) + '.sha256').write_text(sha256(archive.read_bytes()).hexdigest() + '  ' + archive.name + '\n')
print(archive)
