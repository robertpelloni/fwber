import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
    getContacts,
    addContact,
    deleteContact,
    triggerPanic,
    startSafeWalk,
    endSafeWalk,
    getActiveWalk,
    type EmergencyContact,
} from '../api/safety';

export const safetyKeys = {
    all: ['safety'] as const,
    contacts: () => [...safetyKeys.all, 'contacts'] as const,
    activeWalk: () => [...safetyKeys.all, 'walk'] as const,
};

export function useEmergencyContacts() {
    return useQuery({
        queryKey: safetyKeys.contacts(),
        queryFn: getContacts,
    });
}

export function useAddContact() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: (data: Partial<EmergencyContact>) => addContact(data),
        onSuccess: () => qc.invalidateQueries({ queryKey: safetyKeys.contacts() }),
    });
}

export function useDeleteContact() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: (id: number) => deleteContact(id),
        onSuccess: () => qc.invalidateQueries({ queryKey: safetyKeys.contacts() }),
    });
}

export function useTriggerPanic() {
    return useMutation({
        mutationFn: (coords?: { lat: number; lng: number }) =>
            triggerPanic(coords?.lat, coords?.lng),
    });
}

export function useActiveWalk() {
    return useQuery({
        queryKey: safetyKeys.activeWalk(),
        queryFn: getActiveWalk,
        refetchInterval: 10000, // poll every 10s while active
    });
}

export function useStartSafeWalk() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: startSafeWalk,
        onSuccess: () => qc.invalidateQueries({ queryKey: safetyKeys.activeWalk() }),
    });
}

export function useEndSafeWalk() {
    const qc = useQueryClient();
    return useMutation({
        mutationFn: (walkId: number) => endSafeWalk(walkId),
        onSuccess: () => qc.invalidateQueries({ queryKey: safetyKeys.activeWalk() }),
    });
}
