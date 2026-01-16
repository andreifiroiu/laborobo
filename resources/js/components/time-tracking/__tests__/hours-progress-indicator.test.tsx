import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { HoursProgressIndicator } from '../hours-progress-indicator';

describe('HoursProgressIndicator', () => {
    it('shows correct percentage and colors based on progress', () => {
        // Under 80% - should be green
        const { rerender } = render(
            <HoursProgressIndicator actualHours={2} estimatedHours={10} />
        );
        expect(screen.getByText('2.0 / 10.0 hours')).toBeInTheDocument();
        expect(screen.getByText('(20%)')).toBeInTheDocument();
        expect(screen.getByTestId('hours-progress-bar')).toHaveClass('bg-emerald-500');

        // At 80% - should be yellow
        rerender(<HoursProgressIndicator actualHours={8} estimatedHours={10} />);
        expect(screen.getByText('8.0 / 10.0 hours')).toBeInTheDocument();
        expect(screen.getByText('(80%)')).toBeInTheDocument();
        expect(screen.getByTestId('hours-progress-bar')).toHaveClass('bg-amber-500');

        // Over 100% - should be red
        rerender(<HoursProgressIndicator actualHours={12} estimatedHours={10} />);
        expect(screen.getByText('12.0 / 10.0 hours')).toBeInTheDocument();
        expect(screen.getByText('(120%)')).toBeInTheDocument();
        expect(screen.getByTestId('hours-progress-bar')).toHaveClass('bg-red-500');
    });

    it('handles zero estimated hours gracefully', () => {
        render(<HoursProgressIndicator actualHours={5} estimatedHours={0} />);

        expect(screen.getByText('5.0 / 0.0 hours')).toBeInTheDocument();
        // Should show 0% when estimated is 0 (avoid division by zero)
        expect(screen.getByText('(0%)')).toBeInTheDocument();
        // Progress bar should still render without errors
        expect(screen.getByTestId('hours-progress-bar')).toBeInTheDocument();
    });

    it('displays both decimal and time format', () => {
        render(<HoursProgressIndicator actualHours={1.5} estimatedHours={3.25} />);

        // Primary format: "X.X / Y.Y hours (ZZ%)"
        expect(screen.getByText('1.5 / 3.3 hours')).toBeInTheDocument();
        expect(screen.getByText('(46%)')).toBeInTheDocument();

        // Secondary format: "(H:MM / H:MM)"
        expect(screen.getByText('(1:30 / 3:15)')).toBeInTheDocument();
    });
});
