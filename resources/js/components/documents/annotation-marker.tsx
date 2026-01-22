import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import type { AnnotationMarkerProps } from '@/types/documents.d';

/**
 * AnnotationMarker component displays a visual indicator (numbered pin)
 * at an annotation position with hover preview functionality.
 *
 * Features:
 * - Displays numbered marker
 * - Shows comment preview on hover via tooltip
 * - Click handler to open associated comment thread
 * - Active state styling when selected
 * - Accessibility support with proper ARIA attributes
 */
export function AnnotationMarker({
    annotation,
    index,
    onClick,
    isActive = false,
}: AnnotationMarkerProps) {
    const hasPreview = annotation.preview && annotation.preview.content;

    const markerButton = (
        <button
            type="button"
            onClick={onClick}
            data-testid="annotation-marker"
            className={cn(
                // Base styles
                'absolute z-10 flex items-center justify-center',
                'w-6 h-6 -translate-x-1/2 -translate-y-1/2',
                'rounded-full text-xs font-semibold',
                'transition-all duration-150 ease-in-out',
                'cursor-pointer select-none',
                // Default colors
                'bg-primary text-primary-foreground',
                'border-2 border-background',
                'shadow-md',
                // Hover state
                'hover:scale-110 hover:shadow-lg',
                // Focus state for accessibility
                'focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                // Active/selected state
                isActive && 'ring-2 ring-ring ring-offset-2 scale-110'
            )}
            style={{
                left: `${annotation.xPercent}%`,
                top: `${annotation.yPercent}%`,
            }}
            aria-label={`Annotation ${index}${hasPreview ? `: ${annotation.preview?.content.slice(0, 50)}` : ''}`}
        >
            {index}
        </button>
    );

    // Wrap with tooltip if preview is available
    if (hasPreview) {
        return (
            <Tooltip>
                <TooltipTrigger asChild>{markerButton}</TooltipTrigger>
                <TooltipContent
                    side="top"
                    className="max-w-xs"
                    sideOffset={8}
                >
                    <div className="space-y-1">
                        <p className="text-xs font-medium">
                            {annotation.preview?.authorName}
                        </p>
                        <p className="text-xs">{annotation.preview?.content}</p>
                    </div>
                </TooltipContent>
            </Tooltip>
        );
    }

    return markerButton;
}
