import unittest
from unittest.mock import patch

import discover_unused_accounts as discover


class VerifyTests(unittest.TestCase):
    def run_discovery(self, rows, *extra_args):
        argv = ["discover_unused_accounts.py", "--engine", "trino", "-b", "test-bucket", *extra_args]
        with (
            patch("sys.argv", argv),
            patch.object(discover, "TrinoEngine") as engine,
            patch.object(discover, "s3_client") as s3,
            patch.object(discover, "write_work_files") as write,
        ):
            engine.return_value.run.return_value = rows
            if "--verify" in extra_args and rows:
                with self.assertRaises(SystemExit) as result:
                    discover.main()
                self.assertEqual(result.exception.code, 1)
            else:
                discover.main()

            if "--verify" in extra_args:
                s3.assert_not_called()
                write.assert_not_called()
            else:
                write.assert_called_once_with(
                    s3.return_value, "test-bucket", "todo", discover.rows_to_records(rows)
                )

    def test_verify_passes_when_no_unused_accounts_remain(self):
        self.run_discovery([], "--verify")

    def test_verify_fails_when_unused_accounts_remain(self):
        self.run_discovery([["user-id", "identity", "email@example.org", None]], "--verify")

    def test_discover_still_writes_work_files(self):
        self.run_discovery([["user-id", "identity", "email@example.org", None]])

    def test_verify_propagates_query_errors(self):
        with (
            patch("sys.argv", ["discover_unused_accounts.py", "--engine", "trino", "-b", "test", "--verify"]),
            patch.object(discover, "TrinoEngine") as engine,
        ):
            engine.return_value.run.side_effect = RuntimeError("Query failed")
            with self.assertRaisesRegex(RuntimeError, "Query failed"):
                discover.main()


if __name__ == "__main__":
    unittest.main()
