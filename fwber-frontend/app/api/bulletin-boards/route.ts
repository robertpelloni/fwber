import { NextResponse } from 'next/server';

export async function GET(request: Request) {
    try {
        // Proxy to backend express server
        const backendUrl = process.env.NEXT_PUBLIC_BACKEND_URL || 'http://localhost:3001';

        const res = await fetch(`${backendUrl}/bulletin-boards`, {
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
        console.error('Bulletin boards proxy error:', e);
        return NextResponse.json({ error: e.message || 'Failed to fetch boards' }, { status: 500 });
    }
}
