import { NextResponse } from 'next/server';

export async function GET(request: Request) {
    try {
        const backendUrl = process.env.NEXT_PUBLIC_BACKEND_URL || 'http://localhost:3001';

        // Parse search params for 'type' (e.g., global, local, weekly)
        const { searchParams } = new URL(request.url);
        const type = searchParams.get('type') || 'global';

        const res = await fetch(`${backendUrl}/leaderboard?type=${type}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': request.headers.get('authorization') || ''
            }
        });

        if (!res.ok) {
            throw new Error(`Backend error: ${res.status}`);
        }

        const data = await res.json();
        return NextResponse.json(data);
    } catch (e: any) {
        console.error('Leaderboard proxy error:', e);
        return NextResponse.json({ error: e.message || 'Failed to fetch leaderboard' }, { status: 500 });
    }
}
