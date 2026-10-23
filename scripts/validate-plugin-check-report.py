"""Plugin-Check-Rohberichte prüfen, einschließlich unstrukturierter Laufzeitfehler."""
from collections import Counter
from pathlib import Path
import json
import re
import sys


def validate_report(source: str) -> dict:
    if not source.strip():
        raise ValueError('Plugin-Check-Rohbericht ist leer.')
    if re.search(r'wpdberror|WordPress database error|PHP (?:Fatal|Warning|Parse)|Fatal error:|Uncaught [^\n]*(?:Exception|Error)', source, re.I):
        raise ValueError('Plugin-Check-Rohbericht enthält einen Laufzeit- oder Datenbankfehler.')
    parts = re.split(r'^FILE: (.+)\n', source, flags=re.M)
    if len(parts) == 1:
        if re.fullmatch(r'Success: Checks complete\. No errors found\.\s*', source.strip()):
            return {'errors': 0, 'warnings': 0}
        raise ValueError('Unbekannte oder unvollständige Plugin-Check-Ausgabe; Rohbericht prüfen.')
    if parts[0].strip():
        raise ValueError('Unerwartete Ausgabe vor dem ersten strukturierten Prüfergebnis.')
    counts = Counter()
    for index in range(1, len(parts), 2):
        entries = json.loads(parts[index + 1].strip())
        if not isinstance(entries, list):
            raise ValueError('Plugin-Check-Ergebnisse müssen Listen sein.')
        for entry in entries:
            if not isinstance(entry, dict) or not isinstance(entry.get('type'), str) or not isinstance(entry.get('code'), str):
                raise ValueError('Unvollständiger Plugin-Check-Eintrag.')
            counts[entry['type'].upper()] += 1
    errors = sum(count for kind, count in counts.items() if kind.startswith('ERROR'))
    if errors:
        raise ValueError(f'Plugin Check meldet {errors} Fehler.')
    return {'errors': 0, 'warnings': sum(count for kind, count in counts.items() if kind.startswith('WARNING'))}


def main() -> int:
    try:
        result = validate_report(Path(sys.argv[1]).read_text(encoding='utf-8'))
    except (OSError, ValueError, IndexError) as error:
        print(f'FAIL Rohbericht: {error}', file=sys.stderr)
        return 1
    print(json.dumps(result))
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
