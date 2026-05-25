#!/usr/bin/env python3
"""Compare two fwber smoke-check JSON reports and emit drift artifacts.

This script is intentionally self-contained so the Hetzner deploy flow can
compare the newest smoke-check report against the previous one without relying
on extra Python packages.
"""

from __future__ import annotations

import argparse
import json
from pathlib import Path
from typing import Any


def load_report(path: Path) -> dict[str, Any]:
    return json.loads(path.read_text(encoding="utf-8"))


def key_by(items: list[dict[str, Any]], key: str) -> dict[str, dict[str, Any]]:
    result: dict[str, dict[str, Any]] = {}
    for item in items:
        name = str(item.get(key, ""))
        if name:
            result[name] = item
    return result


def compare_summary(previous: dict[str, Any], current: dict[str, Any]) -> dict[str, Any]:
    previous_summary = previous.get("summary", {})
    current_summary = current.get("summary", {})

    return {
        "passes": {
            "previous": previous_summary.get("passes"),
            "current": current_summary.get("passes"),
        },
        "warnings": {
            "previous": previous_summary.get("warnings"),
            "current": current_summary.get("warnings"),
        },
        "failures": {
            "previous": previous_summary.get("failures"),
            "current": current_summary.get("failures"),
        },
        "overall_status": {
            "previous": previous.get("overall_status"),
            "current": current.get("overall_status"),
        },
    }


def compare_diagnostics(previous: dict[str, Any], current: dict[str, Any]) -> dict[str, Any]:
    previous_titles = {str(item.get("title", "")) for item in previous.get("diagnostics", []) if item.get("title")}
    current_titles = {str(item.get("title", "")) for item in current.get("diagnostics", []) if item.get("title")}

    return {
        "new": sorted(current_titles - previous_titles),
        "resolved": sorted(previous_titles - current_titles),
        "unchanged": sorted(previous_titles & current_titles),
    }


def compare_snapshots(previous: dict[str, Any], current: dict[str, Any]) -> list[dict[str, Any]]:
    previous_snapshots = key_by(previous.get("snapshots", []), "label")
    current_snapshots = key_by(current.get("snapshots", []), "label")

    labels = sorted(set(previous_snapshots) | set(current_snapshots))
    changes: list[dict[str, Any]] = []

    interesting_fields = [
        "http_code",
        "remote_ip",
        "server_header",
        "content_type",
        "location_header",
        "effective_url",
    ]

    for label in labels:
        prev = previous_snapshots.get(label)
        curr = current_snapshots.get(label)

        if prev is None:
            changes.append({"label": label, "change_type": "added", "current": curr})
            continue
        if curr is None:
            changes.append({"label": label, "change_type": "removed", "previous": prev})
            continue

        field_changes = {}
        for field in interesting_fields:
            if prev.get(field) != curr.get(field):
                field_changes[field] = {
                    "previous": prev.get(field),
                    "current": curr.get(field),
                }

        if field_changes:
            changes.append({
                "label": label,
                "change_type": "changed",
                "fields": field_changes,
            })

    return changes


def compare_dns(previous: dict[str, Any], current: dict[str, Any]) -> list[dict[str, Any]]:
    previous_dns = key_by(previous.get("dns_records", []), "label")
    current_dns = key_by(current.get("dns_records", []), "label")

    labels = sorted(set(previous_dns) | set(current_dns))
    changes: list[dict[str, Any]] = []

    for label in labels:
        prev = previous_dns.get(label)
        curr = current_dns.get(label)

        if prev is None:
            changes.append({"label": label, "change_type": "added", "current": curr})
            continue
        if curr is None:
            changes.append({"label": label, "change_type": "removed", "previous": prev})
            continue

        if prev.get("addresses") != curr.get("addresses") or prev.get("host") != curr.get("host"):
            changes.append({
                "label": label,
                "change_type": "changed",
                "previous": {
                    "host": prev.get("host"),
                    "addresses": prev.get("addresses"),
                },
                "current": {
                    "host": curr.get("host"),
                    "addresses": curr.get("addresses"),
                },
            })

    return changes


def build_comparison(previous: dict[str, Any], current: dict[str, Any], previous_path: Path, current_path: Path) -> dict[str, Any]:
    return {
        "previous_report": str(previous_path),
        "current_report": str(current_path),
        "summary": compare_summary(previous, current),
        "diagnostics": compare_diagnostics(previous, current),
        "snapshot_changes": compare_snapshots(previous, current),
        "dns_changes": compare_dns(previous, current),
    }


def write_json(output_path: Path, comparison: dict[str, Any]) -> None:
    output_path.write_text(json.dumps(comparison, indent=2), encoding="utf-8")


def write_markdown(output_path: Path, comparison: dict[str, Any]) -> None:
    summary = comparison["summary"]
    diagnostics = comparison["diagnostics"]
    snapshot_changes = comparison["snapshot_changes"]
    dns_changes = comparison["dns_changes"]

    lines = [
        "# Smoke Report Drift Summary",
        "",
        f"- **Previous Report:** `{comparison['previous_report']}`",
        f"- **Current Report:** `{comparison['current_report']}`",
        "",
        "## Summary Delta",
        "",
        f"- Passes: `{summary['passes']['previous']}` → `{summary['passes']['current']}`",
        f"- Warnings: `{summary['warnings']['previous']}` → `{summary['warnings']['current']}`",
        f"- Failures: `{summary['failures']['previous']}` → `{summary['failures']['current']}`",
        f"- Overall Status: `{summary['overall_status']['previous']}` → `{summary['overall_status']['current']}`",
        "",
        "## Diagnostic Drift",
        "",
        f"- New diagnostics: {', '.join(diagnostics['new']) if diagnostics['new'] else 'none'}",
        f"- Resolved diagnostics: {', '.join(diagnostics['resolved']) if diagnostics['resolved'] else 'none'}",
        f"- Unchanged diagnostics: {', '.join(diagnostics['unchanged']) if diagnostics['unchanged'] else 'none'}",
        "",
        "## Endpoint Fingerprint Drift",
        "",
    ]

    if not snapshot_changes:
        lines.append("- No endpoint fingerprint drift detected.")
    else:
        for change in snapshot_changes:
            lines.append(f"### {change['label']} ({change['change_type']})")
            lines.append("")
            if change["change_type"] == "changed":
                for field, values in change["fields"].items():
                    lines.append(f"- **{field}:** `{values['previous']}` → `{values['current']}`")
            elif change["change_type"] == "added":
                lines.append(f"- Added in current report: `{json.dumps(change['current'])}`")
            else:
                lines.append(f"- Missing in current report; previous value: `{json.dumps(change['previous'])}`")
            lines.append("")

    lines.extend(["## DNS Drift", ""])
    if not dns_changes:
        lines.append("- No DNS drift detected.")
    else:
        for change in dns_changes:
            lines.append(f"### {change['label']} ({change['change_type']})")
            lines.append("")
            if change["change_type"] == "changed":
                lines.append(
                    f"- **addresses:** `{change['previous']['addresses']}` → `{change['current']['addresses']}`"
                )
            elif change["change_type"] == "added":
                lines.append(f"- Added in current report: `{json.dumps(change['current'])}`")
            else:
                lines.append(f"- Missing in current report; previous value: `{json.dumps(change['previous'])}`")
            lines.append("")

    output_path.write_text("\n".join(lines) + "\n", encoding="utf-8")


def main() -> int:
    parser = argparse.ArgumentParser(description="Compare two fwber smoke-check JSON reports.")
    parser.add_argument("--previous", required=True, type=Path)
    parser.add_argument("--current", required=True, type=Path)
    parser.add_argument("--json-out", required=True, type=Path)
    parser.add_argument("--md-out", required=True, type=Path)
    args = parser.parse_args()

    previous = load_report(args.previous)
    current = load_report(args.current)
    comparison = build_comparison(previous, current, args.previous, args.current)

    write_json(args.json_out, comparison)
    write_markdown(args.md_out, comparison)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
