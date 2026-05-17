import { NextResponse } from 'next/server';

export async function POST(request: Request) {
    try {
        const body = await request.json();

        // Proxy to backend express server
        const backendUrl = process.env.NEXT_PUBLIC_BACKEND_URL || 'http://localhost:3001';

        const res = await fetch(`${backendUrl}/content-generation/bio`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                // Forward auth token if needed
                'Authorization': request.headers.get('authorization') || ''
            },
            body: JSON.stringify(body)
        });

        if (!res.ok) {
            throw new Error(`Backend error: ${res.status}`);
        }

        const data = await res.json();
        return NextResponse.json(data);
    } catch (e: any) {
        console.error('Content gen proxy error:', e);
        return NextResponse.json({ error: e.message || 'Failed to proxy request' }, { status: 500 });
    }
}
