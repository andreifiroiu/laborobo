<?php

namespace Database\Seeders;

use App\Enums\AIConfidence;
use App\Enums\InboxItemType;
use App\Enums\QAValidation;
use App\Enums\SourceType;
use App\Enums\Urgency;
use App\Models\InboxItem;
use App\Models\Project;
use App\Models\Team;
use App\Models\WorkOrder;
use Illuminate\Database\Seeder;

class InboxItemSeeder extends Seeder
{
    public function run(): void
    {
        // Get all teams
        $teams = Team::all();

        foreach ($teams as $team) {
            // Get some projects and work orders for context
            $projects = Project::forTeam($team->id)->limit(3)->get();
            $workOrders = WorkOrder::forTeam($team->id)->limit(3)->get();

            // Agent Draft - High confidence, passed QA
            InboxItem::create([
                'team_id' => $team->id,
                'type' => InboxItemType::AgentDraft,
                'title' => 'Draft: User Authentication Flow Documentation',
                'content_preview' => 'I\'ve prepared comprehensive documentation for the new authentication flow including OAuth2 integration and multi-factor authentication setup...',
                'full_content' => $this->getAuthDocContent(),
                'source_id' => 'agent-doc-writer-001',
                'source_name' => 'Documentation Agent',
                'source_type' => SourceType::AIAgent,
                'related_work_order_id' => $workOrders->first()?->id,
                'related_work_order_title' => $workOrders->first()?->title,
                'related_project_id' => $projects->first()?->id,
                'related_project_name' => $projects->first()?->name,
                'urgency' => Urgency::Normal,
                'ai_confidence' => AIConfidence::High,
                'qa_validation' => QAValidation::Passed,
                'created_at' => now()->subHours(2),
            ]);

            // Agent Draft - Medium confidence, needs review
            InboxItem::create([
                'team_id' => $team->id,
                'type' => InboxItemType::AgentDraft,
                'title' => 'Draft: API Rate Limiting Implementation Plan',
                'content_preview' => 'Proposed implementation for API rate limiting using Redis. The plan includes token bucket algorithm with configurable limits per user tier...',
                'full_content' => $this->getRateLimitingContent(),
                'source_id' => 'agent-architect-002',
                'source_name' => 'Architecture Agent',
                'source_type' => SourceType::AIAgent,
                'related_work_order_id' => $workOrders->skip(1)->first()?->id,
                'related_work_order_title' => $workOrders->skip(1)->first()?->title,
                'related_project_id' => $projects->first()?->id,
                'related_project_name' => $projects->first()?->name,
                'urgency' => Urgency::High,
                'ai_confidence' => AIConfidence::Medium,
                'qa_validation' => null,
                'created_at' => now()->subHours(5),
            ]);

            // Approval Request - Urgent
            InboxItem::create([
                'team_id' => $team->id,
                'type' => InboxItemType::Approval,
                'title' => 'Approval Needed: Production Deployment Plan',
                'content_preview' => 'The deployment plan for v2.0 is ready for review. Includes database migrations, zero-downtime deployment strategy, and rollback procedures...',
                'full_content' => $this->getDeploymentPlanContent(),
                'source_id' => 'agent-devops-003',
                'source_name' => 'DevOps Agent',
                'source_type' => SourceType::AIAgent,
                'related_work_order_id' => $workOrders->skip(2)->first()?->id,
                'related_work_order_title' => $workOrders->skip(2)->first()?->title,
                'related_project_id' => $projects->skip(1)->first()?->id,
                'related_project_name' => $projects->skip(1)->first()?->name,
                'urgency' => Urgency::Urgent,
                'ai_confidence' => AIConfidence::High,
                'qa_validation' => QAValidation::Passed,
                'created_at' => now()->subMinutes(30),
            ]);

            // Approval Request - Budget approval
            InboxItem::create([
                'team_id' => $team->id,
                'type' => InboxItemType::Approval,
                'title' => 'Budget Approval: Additional Cloud Infrastructure',
                'content_preview' => 'Request to increase monthly cloud budget by $2,000 for additional database replicas and CDN bandwidth to handle increased traffic...',
                'full_content' => $this->getBudgetApprovalContent(),
                'source_id' => 'tm-sarah-johnson',
                'source_name' => 'Sarah Johnson',
                'source_type' => SourceType::Human,
                'related_work_order_id' => null,
                'related_work_order_title' => null,
                'related_project_id' => $projects->skip(1)->first()?->id,
                'related_project_name' => $projects->skip(1)->first()?->name,
                'urgency' => Urgency::High,
                'ai_confidence' => null,
                'qa_validation' => null,
                'created_at' => now()->subHours(8),
            ]);

            // Flagged Item - Low AI confidence
            InboxItem::create([
                'team_id' => $team->id,
                'type' => InboxItemType::Flag,
                'title' => 'Flagged: Potential Security Vulnerability in Login Code',
                'content_preview' => 'Automated security scan detected a potential SQL injection vulnerability in the user login endpoint. Confidence is low but requires manual verification...',
                'full_content' => $this->getSecurityFlagContent(),
                'source_id' => 'agent-security-scanner-004',
                'source_name' => 'Security Scanner Agent',
                'source_type' => SourceType::AIAgent,
                'related_work_order_id' => $workOrders->first()?->id,
                'related_work_order_title' => $workOrders->first()?->title,
                'related_project_id' => $projects->first()?->id,
                'related_project_name' => $projects->first()?->name,
                'urgency' => Urgency::Urgent,
                'ai_confidence' => AIConfidence::Low,
                'qa_validation' => QAValidation::Failed,
                'created_at' => now()->subHours(1),
            ]);

            // Flagged Item - QA Failed
            InboxItem::create([
                'team_id' => $team->id,
                'type' => InboxItemType::Flag,
                'title' => 'QA Failed: Unit Test Coverage Below Threshold',
                'content_preview' => 'The latest code push reduced unit test coverage from 85% to 72%. The following modules need additional test coverage: UserController, PaymentService...',
                'full_content' => $this->getQAFailedContent(),
                'source_id' => 'agent-qa-validator-005',
                'source_name' => 'QA Validation Agent',
                'source_type' => SourceType::AIAgent,
                'related_work_order_id' => $workOrders->skip(1)->first()?->id,
                'related_work_order_title' => $workOrders->skip(1)->first()?->title,
                'related_project_id' => $projects->first()?->id,
                'related_project_name' => $projects->first()?->name,
                'urgency' => Urgency::High,
                'ai_confidence' => AIConfidence::High,
                'qa_validation' => QAValidation::Failed,
                'created_at' => now()->subHours(3),
            ]);

            // Mention - From team member
            InboxItem::create([
                'team_id' => $team->id,
                'type' => InboxItemType::Mention,
                'title' => 'Mentioned in Discussion: Database Migration Strategy',
                'content_preview' => '@you mentioned in project discussion: "We need your input on the database migration strategy for the legacy system integration..."',
                'full_content' => $this->getMentionContent(),
                'source_id' => 'tm-mike-chen',
                'source_name' => 'Mike Chen',
                'source_type' => SourceType::Human,
                'related_work_order_id' => $workOrders->skip(2)->first()?->id,
                'related_work_order_title' => $workOrders->skip(2)->first()?->title,
                'related_project_id' => $projects->skip(2)->first()?->id,
                'related_project_name' => $projects->skip(2)->first()?->name,
                'urgency' => Urgency::Normal,
                'ai_confidence' => null,
                'qa_validation' => null,
                'created_at' => now()->subHours(6),
            ]);

            // Mention - AI suggestion
            InboxItem::create([
                'team_id' => $team->id,
                'type' => InboxItemType::Mention,
                'title' => 'AI Suggestion: Code Review Recommendation',
                'content_preview' => 'The AI code reviewer suggests your review on PR #247. The changes include modifications to the payment processing logic that falls under your expertise...',
                'full_content' => $this->getAISuggestionContent(),
                'source_id' => 'agent-code-reviewer-006',
                'source_name' => 'Code Review Agent',
                'source_type' => SourceType::AIAgent,
                'related_work_order_id' => $workOrders->first()?->id,
                'related_work_order_title' => $workOrders->first()?->title,
                'related_project_id' => $projects->first()?->id,
                'related_project_name' => $projects->first()?->name,
                'urgency' => Urgency::Normal,
                'ai_confidence' => AIConfidence::Medium,
                'qa_validation' => null,
                'created_at' => now()->subHours(4),
            ]);

            // Agent Draft - Older item for testing defer/waiting time
            InboxItem::create([
                'team_id' => $team->id,
                'type' => InboxItemType::AgentDraft,
                'title' => 'Draft: Weekly Status Report - Team Performance Metrics',
                'content_preview' => 'Generated weekly status report including velocity metrics, burndown charts, and team capacity analysis for the past sprint...',
                'full_content' => $this->getStatusReportContent(),
                'source_id' => 'agent-analytics-007',
                'source_name' => 'Analytics Agent',
                'source_type' => SourceType::AIAgent,
                'related_work_order_id' => null,
                'related_work_order_title' => null,
                'related_project_id' => null,
                'related_project_name' => null,
                'urgency' => Urgency::Normal,
                'ai_confidence' => AIConfidence::High,
                'qa_validation' => QAValidation::Passed,
                'created_at' => now()->subHours(24),
            ]);

            // Approval - Client communication
            InboxItem::create([
                'team_id' => $team->id,
                'type' => InboxItemType::Approval,
                'title' => 'Approval: Client Communication - Project Scope Change',
                'content_preview' => 'Draft email to client regarding proposed scope changes and timeline adjustments. Includes cost impact analysis and revised milestones...',
                'full_content' => $this->getClientCommunicationContent(),
                'source_id' => 'agent-communications-008',
                'source_name' => 'Communications Agent',
                'source_type' => SourceType::AIAgent,
                'related_work_order_id' => null,
                'related_work_order_title' => null,
                'related_project_id' => $projects->skip(1)->first()?->id,
                'related_project_name' => $projects->skip(1)->first()?->name,
                'urgency' => Urgency::High,
                'ai_confidence' => AIConfidence::Medium,
                'qa_validation' => null,
                'created_at' => now()->subHours(12),
            ]);
        }
    }

    private function getAuthDocContent(): string
    {
        return <<<'MARKDOWN'
# User Authentication Flow Documentation

## Overview
This document outlines the complete authentication flow for the application, including OAuth2 integration and multi-factor authentication.

## Features
- **OAuth2 Integration**: Support for Google, GitHub, and Microsoft providers
- **Multi-Factor Authentication**: TOTP-based 2FA using authenticator apps
- **Session Management**: Secure session handling with automatic timeout
- **Password Reset**: Secure password reset flow with email verification

## Implementation Details

### OAuth2 Flow
1. User initiates OAuth login
2. Redirect to provider authorization endpoint
3. Provider redirects back with authorization code
4. Exchange code for access token
5. Fetch user profile
6. Create or update user account

### Security Considerations
- All tokens stored encrypted
- CSRF protection on all authentication endpoints
- Rate limiting on login attempts
- Secure session cookies with HttpOnly and SameSite flags

## Testing
- Unit tests for all authentication methods
- Integration tests for OAuth flows
- Security penetration testing completed
MARKDOWN;
    }

    private function getRateLimitingContent(): string
    {
        return <<<'MARKDOWN'
# API Rate Limiting Implementation Plan

## Proposed Solution
Implement token bucket algorithm using Redis for distributed rate limiting across application instances.

## Configuration
- **Free Tier**: 100 requests/hour
- **Pro Tier**: 1,000 requests/hour
- **Enterprise**: 10,000 requests/hour

## Implementation Steps
1. Set up Redis cluster for rate limit counters
2. Create middleware for rate limit checks
3. Add rate limit headers to API responses
4. Implement graceful degradation when limits exceeded
5. Add admin dashboard for monitoring

## Considerations
- Need to handle burst traffic scenarios
- Should we implement sliding window algorithm instead?
- How to handle webhooks and background jobs?

**Questions for Review:**
- Is the free tier limit too restrictive?
- Should we have different limits for different endpoint categories?
MARKDOWN;
    }

    private function getDeploymentPlanContent(): string
    {
        return <<<'MARKDOWN'
# Production Deployment Plan - v2.0

## Deployment Window
**Scheduled**: Saturday, 2:00 AM - 4:00 AM PST
**Expected Downtime**: < 5 minutes

## Pre-Deployment Checklist
- [x] All tests passing on staging
- [x] Database backup completed
- [x] Rollback plan documented
- [x] Team notified
- [ ] Client notification sent
- [ ] Monitoring alerts configured

## Deployment Steps
1. Enable maintenance mode
2. Run database migrations
3. Deploy application code
4. Clear application cache
5. Run smoke tests
6. Disable maintenance mode

## Rollback Procedure
If issues detected:
1. Re-enable maintenance mode
2. Revert database migrations
3. Deploy previous version
4. Restore from backup if needed

## Post-Deployment Monitoring
- Monitor error rates for 2 hours
- Check performance metrics
- Verify critical user flows
- Review logs for anomalies

**APPROVAL REQUIRED** to proceed with deployment.
MARKDOWN;
    }

    private function getBudgetApprovalContent(): string
    {
        return <<<'MARKDOWN'
# Budget Request: Cloud Infrastructure Expansion

## Current Situation
Traffic has increased 300% over the past month, approaching infrastructure capacity limits.

## Requested Budget Increase
**Monthly**: $2,000 additional
**Annual**: $24,000

## Breakdown
- Database read replicas: $800/month
- CDN bandwidth upgrade: $700/month
- Additional application servers: $500/month

## Business Justification
- Current response times degrading during peak hours
- Customer complaints increasing
- Risk of outages during traffic spikes

## ROI Analysis
- Improved performance = better user retention
- Estimated revenue impact: $10,000/month
- Payback period: 2.4 months

## Alternative Considered
Optimize existing infrastructure first - estimated 3-6 months of development time.

**Recommendation**: Approve infrastructure upgrade while pursuing optimization in parallel.
MARKDOWN;
    }

    private function getSecurityFlagContent(): string
    {
        return <<<'MARKDOWN'
# Security Alert: Potential SQL Injection Vulnerability

## Detection Details
- **File**: `app/Http/Controllers/Auth/LoginController.php`
- **Line**: 47
- **Confidence**: Low (potential false positive)
- **Severity**: Critical if confirmed

## Code Snippet
```php
$user = DB::select("SELECT * FROM users WHERE email = '$email'");
```

## Analysis
The code appears to use string concatenation for SQL queries, which could allow SQL injection attacks.

## Recommended Action
1. Review the actual implementation
2. Verify if input sanitization is present
3. Migrate to prepared statements if needed
4. Add security test cases

## False Positive Indicators
- Code might be part of a commented example
- May already be using query builder elsewhere
- Context suggests this is legacy code

**Manual verification required** - automated scan confidence is low.
MARKDOWN;
    }

    private function getQAFailedContent(): string
    {
        return <<<'MARKDOWN'
# QA Validation Failed: Test Coverage Below Threshold

## Summary
Latest commit (SHA: `a7f3c2b`) reduced unit test coverage from 85% to 72%.

## Affected Modules
1. **UserController** - Coverage: 45% (was 90%)
   - Missing tests for new `updateProfile()` method
   - Edge cases not covered in `deleteAccount()`

2. **PaymentService** - Coverage: 60% (was 88%)
   - New refund logic has no tests
   - Error handling paths untested

3. **NotificationHandler** - Coverage: 55% (was 78%)
   - Webhook handling not tested
   - Retry logic missing coverage

## Required Actions
- Add unit tests for all new methods
- Test error handling paths
- Test edge cases and boundary conditions

## Timeline
Tests must be added before merge approval. Estimated time: 4-6 hours.

**Build will remain blocked** until coverage returns to 80% minimum.
MARKDOWN;
    }

    private function getMentionContent(): string
    {
        return <<<'MARKDOWN'
# Discussion: Database Migration Strategy

**Mike Chen** mentioned you in project discussion:

---

Hey team, we're planning the migration from the legacy MySQL database to PostgreSQL and need your expertise.

@you - Given your experience with our current schema, can you review the proposed migration strategy? Specifically:

1. Should we do a big-bang migration or gradual transition?
2. Any concerns about the data transformation scripts?
3. Recommendations for handling the 500GB+ dataset?

The migration plan is attached to the work order. We're targeting next quarter but need to finalize the approach by end of week.

Thanks!
Mike

---

**Related Documents:**
- Migration Plan v3.pdf
- Data Mapping Spreadsheet
- Risk Assessment

Please respond in the work order discussion thread.
MARKDOWN;
    }

    private function getAISuggestionContent(): string
    {
        return <<<'MARKDOWN'
# Code Review Recommendation - PR #247

The AI Code Reviewer has identified that your expertise would be valuable for reviewing this pull request.

## PR Details
- **Title**: Refactor payment processing pipeline
- **Author**: Jessica Martinez
- **Files Changed**: 23
- **Lines**: +847 / -423

## Why Your Review is Suggested
1. You authored the original payment processing code
2. Changes involve Stripe API integration (your specialty)
3. PR includes modifications to refund logic you implemented
4. Complex error handling requires domain expertise

## Key Changes
- Migrated from synchronous to asynchronous payment processing
- Added retry logic for failed transactions
- Implemented webhook verification
- Updated refund handling to support partial refunds

## AI Analysis
- Code quality score: 8.5/10
- Test coverage: 92%
- Performance impact: Positive (estimated 40% faster)
- Security concerns: None detected

## Recommended Focus Areas
1. Webhook signature verification logic
2. Retry mechanism implementation
3. Edge cases in refund calculations

Please review by EOD tomorrow to unblock deployment.
MARKDOWN;
    }

    private function getStatusReportContent(): string
    {
        return <<<'MARKDOWN'
# Weekly Status Report - Sprint 23

## Team Performance Metrics

### Velocity
- **Planned**: 42 story points
- **Completed**: 38 story points
- **Completion Rate**: 90.5%

### Burndown Analysis
Team maintained steady pace throughout sprint with slight slowdown mid-week due to production incident.

## Key Accomplishments
1. ✅ User authentication refactor completed
2. ✅ Payment integration tested and deployed
3. ✅ API documentation updated
4. ⚠️ Mobile app optimization in progress (80% complete)

## Blockers & Risks
- **Blocker**: Waiting on third-party API credentials for integration testing
- **Risk**: Mobile app deadline tight due to unexpected complexity

## Team Capacity
- **Available**: 160 hours
- **Utilized**: 152 hours
- **Meetings**: 12 hours
- **Actual Dev**: 140 hours

## Next Sprint Focus
- Complete mobile app optimization
- Begin work on notification system
- Address technical debt in payment module

## Recommendations
- Schedule knowledge sharing session on new authentication system
- Consider adding one additional developer for mobile team
MARKDOWN;
    }

    private function getClientCommunicationContent(): string
    {
        return <<<'MARKDOWN'
# Draft Email: Project Scope Change Notification

**To:** john.doe@client-company.com
**CC:** project-stakeholders@client-company.com
**Subject:** Project Timeline Update - Additional Features Discussion

---

Dear John,

I hope this email finds you well.

Following our call last week regarding the additional features requested for Phase 2, I wanted to outline the scope changes and their impact on our timeline and budget.

## Requested Additions
1. Advanced analytics dashboard with real-time reporting
2. Multi-language support (5 languages)
3. Custom API integrations for your CRM and ERP systems

## Impact Analysis

### Timeline
- **Original Completion**: March 15, 2026
- **Revised Completion**: April 30, 2026
- **Additional Time**: 6 weeks

### Budget
- **Original Budget**: $85,000
- **Additional Cost**: $22,000
- **Revised Total**: $107,000

## Breakdown
- Analytics dashboard: $8,000 (3 weeks)
- Multi-language support: $6,000 (2 weeks)
- API integrations: $8,000 (1 week + ongoing support)

## Recommendation
Given the value these features will provide, we recommend proceeding with all three additions. However, if budget is a constraint, we can phase the implementation:

**Phase 2A** (Original deadline): Core features only
**Phase 2B** (May): Additional features

Please let me know your preference by Friday so we can adjust our sprint planning accordingly.

Best regards,
[Your Name]

---

**Please approve this communication before sending.**
MARKDOWN;
    }
}
