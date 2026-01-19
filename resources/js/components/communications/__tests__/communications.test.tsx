import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { MentionInput } from '../mention-input';
import { ReactionPicker } from '../reaction-picker';
import { MessageItem } from '../message-item';
import { CommunicationsPanel } from '../communications-panel';

// Mock scrollIntoView
Element.prototype.scrollIntoView = vi.fn();

// Mock fetch for API calls
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Mock router for Inertia
vi.mock('@inertiajs/react', () => ({
    router: {
        post: vi.fn(),
        patch: vi.fn(),
        delete: vi.fn(),
        reload: vi.fn(),
    },
    usePage: () => ({
        props: {
            auth: { user: { id: '1', name: 'Test User' } },
        },
    }),
}));

describe('MentionInput', () => {
    beforeEach(() => {
        mockFetch.mockReset();
        vi.useFakeTimers({ shouldAdvanceTime: true });
    });

    afterEach(() => {
        vi.runOnlyPendingTimers();
        vi.useRealTimers();
    });

    it('triggers autocomplete on @ character', async () => {
        mockFetch.mockResolvedValue({
            ok: true,
            json: () => Promise.resolve({
                results: [
                    { id: '1', name: 'John Doe', type: 'user' },
                    { id: '2', name: 'Jane Smith', type: 'user' },
                ],
            }),
        });

        const onChange = vi.fn();
        render(
            <MentionInput
                value=""
                onChange={onChange}
                placeholder="Type a message..."
            />
        );

        const textarea = screen.getByPlaceholderText('Type a message...');

        // Simulate typing @jo with proper cursor position
        await act(async () => {
            Object.defineProperty(textarea, 'selectionStart', { value: 3, writable: true });
            fireEvent.change(textarea, {
                target: { value: '@jo', selectionStart: 3 },
            });
        });

        // Fast-forward past debounce delay (200ms)
        await act(async () => {
            vi.advanceTimersByTime(300);
        });

        // Verify API was called
        expect(mockFetch).toHaveBeenCalledWith(
            expect.stringContaining('/api/mentions/search'),
            expect.any(Object)
        );
    });

    it('inserts selected mention into content', async () => {
        mockFetch.mockResolvedValue({
            ok: true,
            json: () => Promise.resolve({
                results: [
                    { id: '1', name: 'John Doe', type: 'user', username: 'johndoe' },
                ],
            }),
        });

        const onChange = vi.fn();
        const { rerender } = render(
            <MentionInput
                value=""
                onChange={onChange}
                placeholder="Type a message..."
            />
        );

        const textarea = screen.getByPlaceholderText('Type a message...');

        // Type @jo
        await act(async () => {
            Object.defineProperty(textarea, 'selectionStart', { value: 3, writable: true });
            fireEvent.change(textarea, {
                target: { value: '@jo', selectionStart: 3 },
            });
        });

        // Update the rendered value since onChange is called
        rerender(
            <MentionInput
                value="@jo"
                onChange={onChange}
                placeholder="Type a message..."
            />
        );

        // Fast-forward past debounce delay
        await act(async () => {
            vi.advanceTimersByTime(300);
        });

        // Verify API was called
        expect(mockFetch).toHaveBeenCalled();

        // Wait for suggestion to appear in popover
        await waitFor(() => {
            const suggestion = screen.queryByText('John Doe');
            expect(suggestion).toBeInTheDocument();
        });

        // Click on the suggestion
        const suggestion = screen.getByText('John Doe');
        await act(async () => {
            fireEvent.click(suggestion);
        });

        // Verify onChange was called with the mention inserted
        expect(onChange).toHaveBeenLastCalledWith(expect.stringContaining('@johndoe'));
    });
});

describe('ReactionPicker', () => {
    it('displays emoji options and handles selection', async () => {
        const user = userEvent.setup();
        const onReactionAdd = vi.fn();

        render(
            <ReactionPicker
                messageId="123"
                onReactionAdd={onReactionAdd}
            />
        );

        // Find and click the trigger button
        const triggerButton = screen.getByRole('button', { name: /add reaction/i });
        expect(triggerButton).toBeInTheDocument();

        await user.click(triggerButton);

        // Wait for popover content to appear and check for emoji options
        await waitFor(() => {
            // Look for emoji buttons by their aria-label
            const thumbsUpButton = screen.getByRole('button', { name: /thumbs up/i });
            expect(thumbsUpButton).toBeInTheDocument();
        });

        // Click an emoji
        const thumbsUpButton = screen.getByRole('button', { name: /thumbs up/i });
        await user.click(thumbsUpButton);

        // Verify callback was called
        expect(onReactionAdd).toHaveBeenCalledWith(expect.any(String));
    });
});

describe('MessageItem', () => {
    const baseMessage = {
        id: '1',
        authorId: '1',
        authorName: 'Test User',
        authorType: 'human' as const,
        timestamp: new Date().toISOString(),
        content: 'Test message content',
        type: 'note' as const,
        editedAt: null,
        canEdit: true,
        canDelete: true,
        mentions: [],
        attachments: [],
        reactions: [],
    };

    it('shows edit/delete actions within time window', () => {
        render(
            <MessageItem
                message={baseMessage}
                currentUserId="1"
                onEdit={vi.fn()}
                onDelete={vi.fn()}
            />
        );

        // Message should render with content
        expect(screen.getByText('Test message content')).toBeInTheDocument();

        // Hover over the message to show the actions (they're shown on group-hover)
        const messageContainer = screen.getByText('Test message content').closest('.group');
        expect(messageContainer).toBeInTheDocument();

        // Look for actions menu trigger (visible on hover, but present in DOM)
        const menuTrigger = screen.getByRole('button', { name: /message actions/i });
        expect(menuTrigger).toBeInTheDocument();
    });

    it('hides edit/delete actions after time window', () => {
        const expiredMessage = {
            ...baseMessage,
            canEdit: false,
            canDelete: false,
        };

        render(
            <MessageItem
                message={expiredMessage}
                currentUserId="1"
                onEdit={vi.fn()}
                onDelete={vi.fn()}
            />
        );

        // Message should render with content
        expect(screen.getByText('Test message content')).toBeInTheDocument();

        // Actions menu should not be present when canEdit/canDelete are false
        const menuTrigger = screen.queryByRole('button', { name: /message actions/i });
        expect(menuTrigger).not.toBeInTheDocument();
    });
});

describe('CommunicationsPanel', () => {
    beforeEach(() => {
        mockFetch.mockReset();
        mockFetch.mockResolvedValue({
            ok: true,
            json: () => Promise.resolve({
                thread: { id: '1', messageCount: 2 },
                messages: [
                    {
                        id: '1',
                        authorId: '2',
                        authorName: 'Another User',
                        authorType: 'human',
                        timestamp: new Date().toISOString(),
                        content: 'First message',
                        type: 'note',
                        editedAt: null,
                        canEdit: false,
                        canDelete: false,
                        mentions: [],
                        attachments: [],
                        reactions: [],
                    },
                ],
            }),
        });
    });

    it('renders message list and input', async () => {
        render(
            <CommunicationsPanel
                threadableType="projects"
                threadableId="1"
                open={true}
                onOpenChange={vi.fn()}
            />
        );

        // Wait for the component to render with the sheet content
        await waitFor(() => {
            // The Sheet renders the title in a portal
            expect(screen.getByText('Project Communications')).toBeInTheDocument();
        });

        // Wait for data to load and input to appear
        await waitFor(() => {
            // Check that the message input placeholder is present
            const textarea = screen.getByPlaceholderText(/type a message/i);
            expect(textarea).toBeInTheDocument();
        });
    });
});
