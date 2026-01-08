# Reports Specification

## Overview
Provides visibility into project health, team workload, budget tracking, and operational metrics. Displays 7 core report types as interactive cards on a dashboard, with AI-generated insights and anomaly detection to surface what needs attention.

## User Flows
- **Browse reports dashboard** - User sees all report cards with key metrics at a glance, plus AI insights card showing recommendations and anomalies
- **View report details** - User clicks a report card to see full breakdown with charts, tables, and inline insights
- **Filter by dimension** - User toggles between "By Project", "By Person", or "By Time Period" views on each report
- **Select time period** - User adjusts time range selector on individual reports (last 7/30 days, this month, custom range)
- **Drill into details** - User clicks specific projects, people, or items to see detailed breakdowns
- **Act on AI insights** - User sees alert banners for critical anomalies, dedicated insights card on dashboard, and inline warnings with data (e.g., burn rate 2x estimate)
- **Generate AI summaries** - User requests PM Copilot to generate weekly status summaries or asks Analyst Agent for "what changed this week" reports

## UI Requirements
- Dashboard layout with report cards for: Project Status, Workload, Task Aging, Blockers, Time & Budget, Approvals Velocity, Agent Activity
- Each report card shows key metric summary and trend indicator
- Dedicated "AI Insights" card on dashboard showing all recommendations and detected anomalies
- Alert banners at top of reports when critical anomalies are detected
- Filter toggles on each report: "By Project" / "By Person" / "By Time Period"
- Time range selector on each report card (last 7 days, last 30 days, this month, custom date picker)
- Inline AI insights within reports (warning icons, suggestions next to relevant data)
- Health indicators for projects (on track, at risk, overdue) with color coding
- Visual representations: charts for trends, progress bars for capacity/budget, status indicators
- Drill-down capability: click projects/people/items to see detailed breakdowns
- Empty states when no data or issues detected
- Export options for reports (PDF, CSV)

## Configuration
- shell: true
