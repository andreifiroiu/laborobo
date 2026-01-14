import { router } from '@inertiajs/react';
import type { InboxAction, BulkActionPayload } from '@/types/inbox';

export function useInboxActions() {
    const approveItem = (itemId: string) => {
        router.post(`/inbox/${itemId}/approve`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Optional: Show toast notification
            },
        });
    };

    const rejectItem = (itemId: string, feedback: string) => {
        router.post(`/inbox/${itemId}/reject`, { feedback }, {
            preserveScroll: true,
            onSuccess: () => {
                // Optional: Show toast notification
            },
        });
    };

    const deferItem = (itemId: string) => {
        router.post(`/inbox/${itemId}/defer`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Optional: Show toast notification
            },
        });
    };

    const archiveItem = (itemId: string) => {
        router.delete(`/inbox/${itemId}`, {
            preserveScroll: true,
            onSuccess: () => {
                // Optional: Show toast notification
            },
        });
    };

    const bulkAction = (payload: BulkActionPayload) => {
        router.post('/inbox/bulk', payload, {
            preserveScroll: true,
            onSuccess: () => {
                // Optional: Show toast notification
            },
        });
    };

    return {
        approveItem,
        rejectItem,
        deferItem,
        archiveItem,
        bulkAction,
    };
}
