"""Ein grüner Wrapper darf rohe PHP-/Datenbankfehler nicht überdecken."""
from pathlib import Path
import importlib.util
import unittest

spec = importlib.util.spec_from_file_location('mgd_report', Path(__file__).resolve().parents[1] / 'scripts/validate-plugin-check-report.py')
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


class PluginCheckReportTest(unittest.TestCase):
    def test_clean_success(self):
        self.assertEqual(module.validate_report('Success: Checks complete. No errors found.\n'), {'errors': 0, 'warnings': 0})

    def test_intentional_warning_stays_visible(self):
        self.assertEqual(module.validate_report('FILE: assets/frontend.css\n[{"type":"WARNING","code":"EnqueuedStylesScope"}]\n')['warnings'], 1)

    def test_database_error_before_success_fails(self):
        with self.assertRaises(ValueError):
            module.validate_report('<p class="wpdberror">WordPress database error</p>Success: Checks complete. No errors found.')

    def test_php_error_before_json_fails(self):
        with self.assertRaises(ValueError):
            module.validate_report('PHP Fatal error: synthetic\nFILE: plugin.php\n[]')

    def test_structured_error_fails(self):
        with self.assertRaises(ValueError):
            module.validate_report('FILE: plugin.php\n[{"type":"ERROR","code":"synthetic"}]')

    def test_broken_json_fails(self):
        with self.assertRaises(ValueError):
            module.validate_report('FILE: plugin.php\n[{')

    def test_unknown_prefix_fails(self):
        with self.assertRaises(ValueError):
            module.validate_report('Warning: unexpected output\nFILE: plugin.php\n[]')

    def test_empty_report_fails(self):
        with self.assertRaises(ValueError):
            module.validate_report('')


if __name__ == '__main__':
    unittest.main()
