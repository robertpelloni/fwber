#!/usr/bin/env python3
"""Generate and optionally publish a concise smoke-report notification.

The goal is to turn the detailed smoke-check and drift artifacts into a compact
operator-facing summary that can be stored locally and optionally POSTed to a
webhook (for Slack-style incoming webhook flows or generic automation hooks).
"""

from __future__ import annotations

import argparse
import json
from pathlib import Path
from typing import Any
from urllib import request


def load_json(path: Path | None) -> dict[str, Any] | None:
    if path is None or not path.exists():
        return None
    return json.loads(path.read_text(encoding="utf-8"))


def top_diagnostics(report: dict[str, Any], limit: int = 4) -> list[dict[str, Any]]:
    diagnostics = report.get("diagnostics", [])
    return diagnostics[:limit] if isinstance(diagnostics, list) else []


def build_markdown(summary: dict[str, Any], drift: dict[str, Any] | None, report_dir: Path) -> str:
    status = summary.get("overall_status", "unknown")
    summary_counts = summary.get("summary", {})
    diagnostics = top_diagnostics(summary)

    lines = [
        "# fwber Deploy Smoke Summary",
        "",
        f"- **Status:** `{status}`",
        f"- **Passes:** `{summary_counts.get('passes')}`",
        f"- **Warnings:** `{summary_counts.get('warnings')}`",
        f"- **Failures:** `{summary_counts.get('failures')}`",
        f"- **Report Directory:** `{report_dir}`",
        "",
        "## Key Diagnostics",
        "",
    ]

    if diagnostics:
        for item in diagnostics:
            lines.append(f"- **{item.get('severity', 'unknown')}** — {item.get('title', 'Untitled diagnostic')}")
    else:
        lines.append("- No diagnostics were generated.")

    if drift is not None:
        drift_diagnostics = drift.get("diagnostics", {})
        lines.extend([
            "",
            "## Drift Summary",
            "",
            f"- New diagnostics: {', '.join(drift_diagnostics.get('new', [])) or 'none'}",
            f"- Resolved diagnostics: {', '.join(drift_diagnostics.get('resolved', [])) or 'none'}",
            f"- Unchanged diagnostics: {', '.join(drift_diagnostics.get('unchanged', [])) or 'none'}",
            f"- Snapshot changes: `{len(drift.get('snapshot_changes', []))}`",
            f"- DNS changes: `{len(drift.get('dns_changes', []))}`",
        ])

    lines.extend([
        "",
        "## Artifacts",
        "",
        f"- `smoke-check-summary.json`",
        f"- `smoke-check-summary.md`",
    ])

    if drift is not None:
        lines.append("- `smoke-check-drift.json`")
        lines.append("- `smoke-check-drift.md`")

    return "\n".join(lines) + "\n"


def build_payload(summary: dict[str, Any], drift: dict[str, Any] | None, markdown: str, report_dir: Path) -> dict[str, Any]:
    return {
        "text": markdown,
        "report_dir": str(report_dir),
        "overall_status": summary.get("overall_status"),
        "summary": summary.get("summary", {}),
        "diagnostics": top_diagnostics(summary),
        "drift": drift,
    }


def write_artifacts(payload: dict[str, Any], markdown: str, json_out: Path, md_out: Path) -> None:
    json_out.write_text(json.dumps(payload, indent=2), encoding="utf-8")
    md_out.write_text(markdown, encoding="utf-8")


def publish_webhook(url: str, payload: dict[str, Any]) -> None:
    data = json.dumps(payload).encode("utf-8")
    req = request.Request(url, data=data, headers={"Content-Type": "application/json"}, method="POST")
    with request.urlopen(req, timeout=15) as response:
        if response.status >= 400:
            raise RuntimeError(f"Webhook returned HTTP {response.status}")


def main() -> int:
    parser = argparse.ArgumentParser(description="Generate and optionally publish a concise smoke-report notification.")
    parser.add_argument("--summary-json", required=True, type=Path)
    parser.add_argument("--drift-json", type=Path)
    parser.add_argument("--json-out", required=True, type=Path)
    parser.add_argument("--md-out", required=True, type=Path)
    parser.add_argument("--webhook-url")
    args = parser.parse_args()

    summary = load_json(args.summary_json)
    if summary is None:
        raise SystemExit("Summary JSON could not be loaded.")

    drift = load_json(args.drift_json)
    report_dir = args.json_out.parent
    markdown = build_markdown(summary, drift, report_dir)
    payload = build_payload(summary, drift, markdown, report_dir)

    write_artifacts(payload, markdown, args.json_out, args.md_out)

    if args.webhook_url:
        publish_webhook(args.webhook_url, payload)

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
