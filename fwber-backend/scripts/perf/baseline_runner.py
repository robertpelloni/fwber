import asyncio
import aiohttp
import time
import json
import argparse
import sys
import statistics

async def fetch_url(session, url, headers=None):
    start_time = time.time()
    try:
        async with session.get(url, headers=headers) as response:
            latency = (time.time() - start_time) * 1000  # ms
            status = response.status
            return {"status": status, "latency": latency, "error": None}
    except Exception as e:
        latency = (time.time() - start_time) * 1000
        return {"status": 0, "latency": latency, "error": str(e)}

async def worker(url, duration, results, token=None):
    end_time = time.time() + duration
    headers = {"Authorization": f"Bearer {token}", "Accept": "application/json"} if token else {"Accept": "application/json"}

    async with aiohttp.ClientSession() as session:
        while time.time() < end_time:
            result = await fetch_url(session, url, headers)
            results.append(result)
            await asyncio.sleep(0.1)  # small delay to prevent immediate hammering

async def run_load_test(url, vus, duration, token=None):
    print(f"Starting load test on {url} with {vus} VUs for {duration} seconds...")
    results = []
    tasks = [worker(url, duration, results, token) for _ in range(vus)]
    await asyncio.gather(*tasks)

    total_reqs = len(results)
    success_reqs = len([r for r in results if r["status"] == 200])
    failed_reqs = total_reqs - success_reqs
    latencies = [r["latency"] for r in results if r["status"] == 200]

    if not latencies:
        print("No successful requests.")
        if results:
            print(f"Sample error: {results[0]}")
        return {
            "total_requests": total_reqs,
            "success_rate": 0,
            "avg_latency_ms": 0,
            "p95_latency_ms": 0,
            "requests_per_second": 0,
            "timestamp": time.time()
        }

    avg_latency = statistics.mean(latencies)
    p95_latency = statistics.quantiles(latencies, n=20)[18] if len(latencies) >= 20 else max(latencies)
    rps = total_reqs / duration

    report = {
        "timestamp": time.time(),
        "url": url,
        "vus": vus,
        "duration": duration,
        "total_requests": total_reqs,
        "success_requests": success_reqs,
        "failed_requests": failed_reqs,
        "success_rate": (success_reqs / total_reqs) * 100,
        "avg_latency_ms": round(avg_latency, 2),
        "p95_latency_ms": round(p95_latency, 2),
        "requests_per_second": round(rps, 2)
    }

    return report

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Basic HTTP Load Tester")
    parser.add_argument("--url", default="http://localhost:8000/api/health", help="Target URL")
    parser.add_argument("--vus", type=int, default=10, help="Number of concurrent users")
    parser.add_argument("--duration", type=int, default=10, help="Test duration in seconds")
    parser.add_argument("--token", help="Bearer token for auth (optional)")
    parser.add_argument("--output", default="baseline_report.json", help="Output file")

    args = parser.parse_args()

    try:
        report = asyncio.run(run_load_test(args.url, args.vus, args.duration, args.token))

        print("\n--- Test Results ---")
        print(f"Total Requests: {report['total_requests']}")
        print(f"Success Rate: {report['success_rate']:.2f}%")
        print(f"Avg Latency: {report['avg_latency_ms']} ms")
        print(f"P95 Latency: {report['p95_latency_ms']} ms")
        print(f"RPS: {report['requests_per_second']}")

        with open(args.output, "w") as f:
            json.dump(report, f, indent=2)

        print(f"\nReport saved to {args.output}")

    except KeyboardInterrupt:
        print("\nTest interrupted.")
        sys.exit(1)
