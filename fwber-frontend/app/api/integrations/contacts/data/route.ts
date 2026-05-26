import { NextResponse } from 'next/server';

export async function GET(request: Request) {
    try {
        const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:4000/api';
        const res = await fetch(`${apiUrl}/integrations/contacts/data`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': request.headers.get('authorization') || ''
            }
        });

        if (!res.ok) throw new Error(`Backend error: ${res.status}`);

        const data = await res.json();
        return NextResponse.json(data);
    } catch (e: any) {
        return NextResponse.json({ error: e.message || 'Failed to fetch contacts' }, { status: 500 });
    }
}

export async function POST(request: Request) {
    try {
        const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:4000/api';
        const res = await fetch(`${apiUrl}/integrations/contacts/data/sync`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': request.headers.get('authorization') || ''
            }
        });

        if (!res.ok) throw new Error(`Backend error: ${res.status}`);

        const data = await res.json();
        return NextResponse.json(data);
    } catch (e: any) {
        return NextResponse.json({ error: e.message || 'Failed to trigger sync' }, { status: 500 });
    }
}
