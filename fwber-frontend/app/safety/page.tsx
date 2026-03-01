'use client';

import { useState, useEffect, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/lib/auth-context';
import {
    useEmergencyContacts,
    useAddContact,
    useDeleteContact,
    useTriggerPanic,
    useActiveWalk,
    useStartSafeWalk,
    useEndSafeWalk,
} from '@/lib/hooks/use-safety';
import {
    ShieldAlert,
    UserPlus,
    Trash2,
    Navigation,
    MapPin,
    Phone,
    Mail,
    Footprints,
    X,
    AlertTriangle,
    CheckCircle,
    Sparkles,
} from 'lucide-react';

export default function SafetyPage() {
    const { isAuthenticated } = useAuth();
    const router = useRouter();

    // State
    const [showAddContact, setShowAddContact] = useState(false);
    const [contactName, setContactName] = useState('');
    const [contactPhone, setContactPhone] = useState('');
    const [contactEmail, setContactEmail] = useState('');
    const [panicTriggered, setPanicTriggered] = useState(false);
    const [destination, setDestination] = useState('');
    const [coords, setCoords] = useState<{ lat: number; lng: number } | null>(null);

    // Hooks
    const { data: contactsData } = useEmergencyContacts();
    const addContact = useAddContact();
    const deleteContact = useDeleteContact();
    const panic = useTriggerPanic();
    const { data: walkData } = useActiveWalk();
    const startWalk = useStartSafeWalk();
    const endWalk = useEndSafeWalk();

    const activeWalk = walkData?.walk;

    // Get location
    useEffect(() => {
        if (!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition(
            (pos) => setCoords({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
            () => { }
        );
    }, []);

    const handlePanic = useCallback(() => {
        panic.mutate(coords || undefined, {
            onSuccess: () => setPanicTriggered(true),
        });
    }, [coords, panic]);

    const handleAddContact = () => {
        if (!contactName.trim()) return;
        addContact.mutate(
            { name: contactName, phone: contactPhone || null, email: contactEmail || null, is_primary: false },
            {
                onSuccess: () => {
                    setContactName(''); setContactPhone(''); setContactEmail('');
                    setShowAddContact(false);
                },
            }
        );
    };

    const handleStartWalk = () => {
        if (!coords) return;
        startWalk.mutate({
            destination: destination || undefined,
            start_lat: coords.lat,
            start_lng: coords.lng,
        });
    };

    if (!isAuthenticated) {
        return (
            <div className="sf-empty">
                <ShieldAlert size={48} />
                <h1>Safety Center</h1>
                <p>Sign in to access safety features.</p>
                <button onClick={() => router.push('/login')} className="sf-primary-btn">Log In</button>
            </div>
        );
    }

    return (
        <>
            <style jsx global>{`
        .sf-page {
          min-height: 100vh;
          background: linear-gradient(160deg, #0a0a1a 0%, #1a0a0a 40%, #0f1628 100%);
          color: #e0e0ff; padding: 2rem 1rem;
          font-family: 'Inter', -apple-system, sans-serif;
        }
        .sf-container { max-width: 600px; margin: 0 auto; }
        .sf-header { text-align: center; margin-bottom: 2rem; }
        .sf-header h1 {
          font-size: 2rem; font-weight: 700;
          background: linear-gradient(to right, #ef4444, #f59e0b);
          -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .sf-header p { color: #7c7caa; font-size: 0.9rem; }

        /* PANIC BUTTON */
        .sf-panic-zone {
          text-align: center; margin-bottom: 2.5rem;
        }
        .sf-panic-btn {
          width: 160px; height: 160px; border-radius: 50%;
          background: radial-gradient(circle, #ef4444 0%, #991b1b 100%);
          border: 4px solid rgba(239, 68, 68, 0.3);
          color: white; font-size: 1.1rem; font-weight: 700;
          cursor: pointer; display: flex; flex-direction: column;
          align-items: center; justify-content: center; gap: 6px;
          margin: 0 auto;
          box-shadow: 0 0 40px rgba(239, 68, 68, 0.3), inset 0 0 20px rgba(0, 0, 0, 0.2);
          transition: transform 0.15s, box-shadow 0.2s;
          animation: sf-pulse 2s infinite;
        }
        .sf-panic-btn:hover {
          transform: scale(1.05);
          box-shadow: 0 0 60px rgba(239, 68, 68, 0.5);
        }
        .sf-panic-btn:active { transform: scale(0.95); }
        @keyframes sf-pulse {
          0%, 100% { box-shadow: 0 0 40px rgba(239, 68, 68, 0.3); }
          50% { box-shadow: 0 0 60px rgba(239, 68, 68, 0.5); }
        }
        .sf-panic-label {
          font-size: 0.75rem; color: #7c7caa;
          margin-top: 0.75rem; text-align: center;
        }

        .sf-panic-result {
          text-align: center; padding: 1.5rem;
          background: rgba(239, 68, 68, 0.1);
          border: 1px solid rgba(239, 68, 68, 0.3);
          border-radius: 16px; margin-bottom: 2rem;
        }
        .sf-panic-result h3 { color: #ef4444; font-size: 1.1rem; margin-bottom: 0.5rem; }
        .sf-panic-result p { color: #a0a0cc; font-size: 0.85rem; }

        /* SECTIONS */
        .sf-section {
          background: rgba(20, 15, 35, 0.6);
          border: 1px solid rgba(100, 100, 200, 0.12);
          border-radius: 16px; padding: 1.25rem;
          margin-bottom: 1.5rem;
        }
        .sf-section-header {
          display: flex; justify-content: space-between;
          align-items: center; margin-bottom: 1rem;
        }
        .sf-section-title {
          font-size: 1rem; font-weight: 600; color: #e0e0ff;
          display: flex; align-items: center; gap: 8px;
        }
        .sf-add-btn {
          display: flex; align-items: center; gap: 4px;
          padding: 6px 12px; border-radius: 8px;
          background: rgba(99, 102, 241, 0.15);
          border: 1px solid rgba(99, 102, 241, 0.3);
          color: #818cf8; font-size: 0.8rem; font-weight: 500;
          cursor: pointer;
        }

        /* CONTACTS */
        .sf-contact {
          display: flex; justify-content: space-between;
          align-items: center; padding: 0.75rem;
          background: rgba(10, 10, 25, 0.5);
          border-radius: 10px; margin-bottom: 0.5rem;
        }
        .sf-contact-info h4 { font-size: 0.9rem; color: #e0e0ff; margin-bottom: 2px; }
        .sf-contact-meta {
          display: flex; gap: 0.75rem; font-size: 0.75rem; color: #7c7caa;
        }
        .sf-contact-meta span { display: flex; align-items: center; gap: 3px; }
        .sf-del-btn {
          background: none; border: none; color: #5a5a8a;
          cursor: pointer; padding: 4px;
        }
        .sf-del-btn:hover { color: #ef4444; }

        /* ADD FORM */
        .sf-add-form {
          padding: 1rem; background: rgba(10, 10, 25, 0.5);
          border-radius: 12px; margin-bottom: 0.5rem;
        }
        .sf-input {
          width: 100%; padding: 0.6rem 0.75rem;
          background: rgba(20, 20, 40, 0.8);
          border: 1px solid rgba(100, 100, 200, 0.2);
          border-radius: 8px; color: #e0e0ff; font-size: 0.85rem;
          margin-bottom: 0.5rem;
        }
        .sf-input::placeholder { color: #5a5a8a; }
        .sf-input:focus { outline: none; border-color: rgba(99, 102, 241, 0.5); }
        .sf-form-actions {
          display: flex; gap: 0.5rem; margin-top: 0.5rem;
        }
        .sf-save-btn {
          flex: 1; padding: 0.6rem;
          background: linear-gradient(135deg, #6366f1, #818cf8);
          color: white; border: none; border-radius: 8px;
          font-size: 0.85rem; font-weight: 600; cursor: pointer;
        }
        .sf-cancel-btn {
          padding: 0.6rem 1rem;
          background: rgba(30, 30, 60, 0.6);
          border: 1px solid rgba(100, 100, 200, 0.2);
          border-radius: 8px; color: #7c7caa;
          font-size: 0.85rem; cursor: pointer;
        }

        /* SAFE WALK */
        .sf-walk-active {
          text-align: center; padding: 1.5rem;
          background: rgba(16, 185, 129, 0.08);
          border: 1px solid rgba(16, 185, 129, 0.25);
          border-radius: 16px;
        }
        .sf-walk-active h3 {
          color: #34d399; font-size: 1rem; margin-bottom: 0.5rem;
          display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .sf-walk-active p { color: #7c7caa; font-size: 0.85rem; margin-bottom: 1rem; }
        .sf-end-walk-btn {
          padding: 0.65rem 2rem;
          background: linear-gradient(135deg, #10b981, #059669);
          color: white; border: none; border-radius: 10px;
          font-size: 0.9rem; font-weight: 600; cursor: pointer;
        }
        .sf-start-walk {
          display: flex; gap: 0.5rem;
        }
        .sf-walk-input {
          flex: 1; padding: 0.6rem 0.75rem;
          background: rgba(10, 10, 25, 0.8);
          border: 1px solid rgba(100, 100, 200, 0.2);
          border-radius: 8px; color: #e0e0ff; font-size: 0.85rem;
        }
        .sf-walk-input::placeholder { color: #5a5a8a; }
        .sf-walk-btn {
          display: flex; align-items: center; gap: 6px;
          padding: 0.6rem 1rem;
          background: linear-gradient(135deg, #10b981, #059669);
          color: white; border: none; border-radius: 8px;
          font-size: 0.85rem; font-weight: 600; cursor: pointer;
          white-space: nowrap;
        }
        .sf-walk-btn:disabled { opacity: 0.5; }

        .sf-empty {
          min-height: 100vh;
          background: linear-gradient(160deg, #0a0a1a 0%, #1a0a0a 40%, #0f1628 100%);
          display: flex; flex-direction: column;
          align-items: center; justify-content: center;
          gap: 1rem; text-align: center; padding: 2rem; color: #7c7caa;
        }
        .sf-empty h1 { font-size: 1.6rem; color: #e0e0ff; }
        .sf-primary-btn {
          padding: 0.75rem 2rem;
          background: linear-gradient(135deg, #ef4444, #f59e0b);
          color: white; border: none; border-radius: 12px;
          font-size: 1rem; font-weight: 600; cursor: pointer;
        }
      `}</style>

            <div className="sf-page">
                <div className="sf-container">
                    <div className="sf-header">
                        <h1>🛡️ Safety Center</h1>
                        <p>Your safety is our top priority</p>
                    </div>

                    {/* PANIC BUTTON */}
                    <div className="sf-panic-zone">
                        {panicTriggered && panic.data ? (
                            <div className="sf-panic-result">
                                <AlertTriangle size={32} style={{ color: '#ef4444', marginBottom: '0.5rem' }} />
                                <h3>Panic Alert Sent</h3>
                                <p>{panic.data.alerts_sent} emergency contact(s) notified</p>
                                <p style={{ marginTop: '0.5rem', fontSize: '0.75rem' }}>
                                    {panic.data.contacts_notified.join(', ')}
                                </p>
                            </div>
                        ) : (
                            <>
                                <button className="sf-panic-btn" onClick={handlePanic} disabled={panic.isPending}>
                                    <ShieldAlert size={36} />
                                    {panic.isPending ? 'Sending...' : 'PANIC'}
                                </button>
                                <p className="sf-panic-label">
                                    Tap to silently alert your emergency contacts with your location
                                </p>
                            </>
                        )}
                    </div>

                    {/* SAFE WALK */}
                    <div className="sf-section">
                        <div className="sf-section-header">
                            <span className="sf-section-title"><Footprints size={18} /> Safe Walk Home</span>
                        </div>
                        {activeWalk ? (
                            <div className="sf-walk-active">
                                <h3><Navigation size={16} /> Walk Active</h3>
                                <p>
                                    {activeWalk.destination ? `Heading to: ${activeWalk.destination}` : 'Sharing your location with contacts'}
                                </p>
                                <p style={{ fontSize: '0.75rem' }}>
                                    Started {new Date(activeWalk.started_at || '').toLocaleTimeString()}
                                </p>
                                <button
                                    className="sf-end-walk-btn"
                                    onClick={() => endWalk.mutate(activeWalk.id)}
                                    disabled={endWalk.isPending}
                                >
                                    <CheckCircle size={14} style={{ display: 'inline', marginRight: 4 }} />
                                    {endWalk.isPending ? 'Ending...' : 'Arrived Safely'}
                                </button>
                            </div>
                        ) : (
                            <div className="sf-start-walk">
                                <input
                                    className="sf-walk-input"
                                    placeholder="Where are you headed? (optional)"
                                    value={destination}
                                    onChange={(e) => setDestination(e.target.value)}
                                />
                                <button
                                    className="sf-walk-btn"
                                    onClick={handleStartWalk}
                                    disabled={!coords || startWalk.isPending}
                                >
                                    <Footprints size={14} />
                                    {startWalk.isPending ? 'Starting...' : 'Start Walk'}
                                </button>
                            </div>
                        )}
                    </div>

                    {/* EMERGENCY CONTACTS */}
                    <div className="sf-section">
                        <div className="sf-section-header">
                            <span className="sf-section-title"><Phone size={18} /> Emergency Contacts</span>
                            <button className="sf-add-btn" onClick={() => setShowAddContact(!showAddContact)}>
                                {showAddContact ? <X size={14} /> : <UserPlus size={14} />}
                                {showAddContact ? 'Cancel' : 'Add'}
                            </button>
                        </div>

                        {showAddContact && (
                            <div className="sf-add-form">
                                <input className="sf-input" placeholder="Name *" value={contactName} onChange={(e) => setContactName(e.target.value)} />
                                <input className="sf-input" placeholder="Phone" value={contactPhone} onChange={(e) => setContactPhone(e.target.value)} />
                                <input className="sf-input" placeholder="Email" value={contactEmail} onChange={(e) => setContactEmail(e.target.value)} />
                                <div className="sf-form-actions">
                                    <button className="sf-save-btn" onClick={handleAddContact} disabled={!contactName.trim()}>
                                        Save Contact
                                    </button>
                                    <button className="sf-cancel-btn" onClick={() => setShowAddContact(false)}>Cancel</button>
                                </div>
                            </div>
                        )}

                        {contactsData?.contacts && contactsData.contacts.length > 0 ? (
                            contactsData.contacts.map((c) => (
                                <div key={c.id} className="sf-contact">
                                    <div className="sf-contact-info">
                                        <h4>{c.name} {c.is_primary && '⭐'}</h4>
                                        <div className="sf-contact-meta">
                                            {c.phone && <span><Phone size={10} /> {c.phone}</span>}
                                            {c.email && <span><Mail size={10} /> {c.email}</span>}
                                        </div>
                                    </div>
                                    <button className="sf-del-btn" onClick={() => deleteContact.mutate(c.id)}>
                                        <Trash2 size={14} />
                                    </button>
                                </div>
                            ))
                        ) : !showAddContact ? (
                            <p style={{ color: '#5a5a8a', fontSize: '0.85rem', textAlign: 'center', padding: '1rem' }}>
                                No emergency contacts yet. Add one to enable panic alerts.
                            </p>
                        ) : null}
                    </div>
                </div>
            </div>
        </>
    );
}
