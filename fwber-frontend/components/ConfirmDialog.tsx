'use client';

import { useState, useCallback, useRef } from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

interface ConfirmOptions {
  title: string;
  message: string;
  confirmLabel?: string;
  cancelLabel?: string;
  variant?: 'default' | 'destructive';
}

interface ConfirmState extends ConfirmOptions {
  open: boolean;
  onConfirm: () => void;
  onCancel: () => void;
}

/**
 * Hook that provides a `confirmAction` function to replace native `confirm()`.
 *
 * Usage:
 *   const { confirmAction, ConfirmDialog } = useConfirmDialog();
 *   const handleClick = () => confirmAction({
 *     title: 'Delete?',
 *     message: 'This cannot be undone.',
 *     variant: 'destructive',
 *     onConfirm: () => doDelete(),
 *   });
 */
export function useConfirmDialog() {
  const [state, setState] = useState<ConfirmState | null>(null);

  const confirmAction = useCallback((options: ConfirmOptions & { onConfirm: () => void }) => {
    const { onConfirm, ...rest } = options;
    setState({
      ...rest,
      open: true,
      onConfirm: () => {
        setState(prev => prev ? { ...prev, open: false } : null);
        onConfirm();
      },
      onCancel: () => {
        setState(prev => prev ? { ...prev, open: false } : null);
      },
    });
  }, []);

  const ConfirmDialogComponent = state ? (
    <Dialog open={state.open} onOpenChange={(open) => { if (!open) state.onCancel(); }}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{state.title}</DialogTitle>
          <DialogDescription>{state.message}</DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="outline" onClick={state.onCancel}>
            {state.cancelLabel || 'Cancel'}
          </Button>
          <Button
            variant={state.variant === 'destructive' ? 'destructive' : 'default'}
            onClick={state.onConfirm}
          >
            {state.confirmLabel || 'Confirm'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  ) : null;

  return { confirmAction, ConfirmDialog: ConfirmDialogComponent };
}
