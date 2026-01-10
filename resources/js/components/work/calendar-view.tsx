import { useState } from 'react';
import { ChevronLeft, ChevronRight, Calendar as CalendarIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { CalendarEvent } from './calendar-event';
import type { Project, WorkOrder } from '@/types/work';

interface CalendarViewProps {
    projects: Project[];
    workOrders: WorkOrder[];
}

export function CalendarView({ projects, workOrders }: CalendarViewProps) {
    const [currentDate, setCurrentDate] = useState(new Date());

    // Get calendar data for current month
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay(); // 0 = Sunday

    // Generate calendar grid (including previous month overflow days)
    const calendarDays: Array<{
        date: Date;
        isCurrentMonth: boolean;
        dayNumber: number;
    }> = [];

    // Previous month overflow days
    if (startingDayOfWeek > 0) {
        const prevMonthLastDay = new Date(year, month, 0).getDate();
        for (let i = startingDayOfWeek - 1; i >= 0; i--) {
            calendarDays.push({
                date: new Date(year, month - 1, prevMonthLastDay - i),
                isCurrentMonth: false,
                dayNumber: prevMonthLastDay - i,
            });
        }
    }

    // Current month days
    for (let day = 1; day <= daysInMonth; day++) {
        calendarDays.push({
            date: new Date(year, month, day),
            isCurrentMonth: true,
            dayNumber: day,
        });
    }

    // Next month overflow days to fill grid
    const remainingDays = 42 - calendarDays.length; // 6 rows × 7 days
    for (let day = 1; day <= remainingDays; day++) {
        calendarDays.push({
            date: new Date(year, month + 1, day),
            isCurrentMonth: false,
            dayNumber: day,
        });
    }

    // Get items for a specific date
    const getItemsForDate = (date: Date) => {
        const dateStr = date.toISOString().split('T')[0];

        const projectsOnDate = projects.filter((p) => {
            if (!p.targetEndDate) return false;
            return p.targetEndDate.startsWith(dateStr);
        });

        const workOrdersOnDate = workOrders.filter((wo) => {
            return wo.dueDate.startsWith(dateStr);
        });

        return { projects: projectsOnDate, workOrders: workOrdersOnDate };
    };

    // Navigation
    const goToPreviousMonth = () => {
        setCurrentDate(new Date(year, month - 1, 1));
    };

    const goToNextMonth = () => {
        setCurrentDate(new Date(year, month + 1, 1));
    };

    const goToToday = () => {
        setCurrentDate(new Date());
    };

    const monthName = currentDate.toLocaleString('default', { month: 'long', year: 'numeric' });

    // Check if date is today
    const isToday = (date: Date) => {
        const today = new Date();
        return (
            date.getDate() === today.getDate() &&
            date.getMonth() === today.getMonth() &&
            date.getFullYear() === today.getFullYear()
        );
    };

    // Stats
    const totalItems = projects.length + workOrders.length;
    const overdueItems = [
        ...projects.filter((p) => p.targetEndDate && new Date(p.targetEndDate) < new Date()),
        ...workOrders.filter((wo) => new Date(wo.dueDate) < new Date()),
    ].length;

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                    <h2 className="text-2xl font-bold text-foreground">{monthName}</h2>
                    <Button variant="ghost" size="sm" onClick={goToToday}>
                        Today
                    </Button>
                </div>

                <div className="flex items-center gap-2">
                    <div className="flex items-center gap-1 px-3 py-1.5 bg-card border border-border rounded-lg text-sm">
                        <CalendarIcon className="w-4 h-4 text-muted-foreground" />
                        <span className="font-medium text-foreground">{totalItems}</span>
                        <span className="text-muted-foreground">items</span>
                        {overdueItems > 0 && (
                            <>
                                <span className="mx-1 text-border">•</span>
                                <span className="font-medium text-destructive">{overdueItems}</span>
                                <span className="text-muted-foreground">overdue</span>
                            </>
                        )}
                    </div>

                    <div className="flex items-center gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={goToPreviousMonth}
                            aria-label="Previous month"
                        >
                            <ChevronLeft className="w-5 h-5" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={goToNextMonth}
                            aria-label="Next month"
                        >
                            <ChevronRight className="w-5 h-5" />
                        </Button>
                    </div>
                </div>
            </div>

            {/* Calendar Grid */}
            <div className="bg-card border border-border rounded-xl overflow-hidden">
                {/* Day headers */}
                <div className="grid grid-cols-7 border-b border-border bg-muted">
                    {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map((day) => (
                        <div
                            key={day}
                            className="px-2 py-3 text-xs font-bold text-muted-foreground text-center uppercase tracking-wide"
                        >
                            {day}
                        </div>
                    ))}
                </div>

                {/* Calendar days */}
                <div className="grid grid-cols-7">
                    {calendarDays.map((day, index) => {
                        const items = getItemsForDate(day.date);
                        const hasItems = items.projects.length > 0 || items.workOrders.length > 0;
                        const isTodayDate = isToday(day.date);

                        return (
                            <div
                                key={index}
                                className={`min-h-[120px] p-2 border-b border-r border-border ${
                                    !day.isCurrentMonth ? 'bg-muted/50' : ''
                                } ${index % 7 === 6 ? 'border-r-0' : ''} ${
                                    index >= 35 ? 'border-b-0' : ''
                                }`}
                            >
                                <div
                                    className={`inline-flex items-center justify-center w-6 h-6 mb-1 text-sm font-semibold rounded-full ${
                                        isTodayDate
                                            ? 'bg-primary text-primary-foreground'
                                            : day.isCurrentMonth
                                              ? 'text-foreground'
                                              : 'text-muted-foreground'
                                    }`}
                                >
                                    {day.dayNumber}
                                </div>

                                {hasItems && (
                                    <div className="space-y-1">
                                        {items.projects.map((project) => (
                                            <CalendarEvent
                                                key={project.id}
                                                item={project}
                                                type="project"
                                            />
                                        ))}
                                        {items.workOrders.map((workOrder) => (
                                            <CalendarEvent
                                                key={workOrder.id}
                                                item={workOrder}
                                                type="workOrder"
                                            />
                                        ))}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>
            </div>

            {/* Legend */}
            <div className="flex flex-wrap items-center gap-4 text-xs">
                <div className="flex items-center gap-2">
                    <div className="w-3 h-3 rounded bg-indigo-100 dark:bg-indigo-950/30 border border-indigo-300 dark:border-indigo-800" />
                    <span className="text-muted-foreground">Projects</span>
                </div>
                <div className="flex items-center gap-2">
                    <div className="w-3 h-3 rounded bg-emerald-100 dark:bg-emerald-950/30 border border-emerald-300 dark:border-emerald-800" />
                    <span className="text-muted-foreground">Work Orders</span>
                </div>
                <div className="flex items-center gap-2">
                    <div className="w-3 h-3 rounded bg-red-100 dark:bg-red-950/30 border border-red-300 dark:border-red-800" />
                    <span className="text-muted-foreground">Overdue</span>
                </div>
                <div className="flex items-center gap-2">
                    <div className="w-3 h-3 rounded bg-orange-100 dark:bg-orange-950/30 border border-orange-300 dark:border-orange-800" />
                    <span className="text-muted-foreground">High Priority</span>
                </div>
            </div>
        </div>
    );
}
