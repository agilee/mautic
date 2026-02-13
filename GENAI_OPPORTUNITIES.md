# GenAI Enhancement Opportunities for Mautic

## Executive Summary

Mautic is an open-source marketing automation platform with **zero existing AI/ML capabilities** (confirmed by codebase search). The platform has 28 core bundles and 12 plugins covering email marketing, campaign automation, lead management, segmentation, forms, landing pages, dynamic content, SMS, notifications, and reporting.

This analysis identifies **10 high-impact areas** where Generative AI can transform Mautic from a rule-based automation tool into an intelligent marketing platform.

---

## Current State: What Marketers Do Manually Today

| Area | Current Manual Process | Key Files |
|------|----------------------|-----------|
| **Email Content** | Write subject lines, body copy, preheader text manually in GrapesJS builder | `EmailBundle/Entity/Email.php`, `plugins/GrapesJsBuilderBundle/` |
| **A/B Testing** | Manually create variants and interpret results | `Email.php` variant fields, `VariantEntityTrait` |
| **Segmentation** | Define segment rules with manual filter conditions | `LeadBundle/Segment/ContactSegmentService.php` |
| **Lead Scoring** | Set static point values for actions | `PointBundle/Entity/Point.php`, `TriggerEvent.php` |
| **Campaign Design** | Manually build event trees (actions, conditions, decisions) | `CampaignBundle/Entity/Event.php` (3 types: action, condition, decision) |
| **Send-Time Optimization** | Basic statistical median calculation | `LeadBundle/Services/PeakInteractionTimer.php` |
| **Dynamic Content** | Manually write content variants with rule-based targeting | `DynamicContentBundle/Entity/DynamicContent.php` |
| **Reports** | Manually configure report columns and filters | `ReportBundle/` |
| **Forms** | Manually design fields and validation | `FormBundle/Entity/Form.php` |
| **Landing Pages** | Manually build page layouts and copy | `PageBundle/` |

---

## GenAI Enhancement Opportunities

### 1. AI-Powered Email Content Generation

**Where in the code**: `EmailBundle/`, `plugins/GrapesJsBuilderBundle/`

**Current state**: Marketers manually write every element — subject lines (`Email.php:subject`), preheader text (`Email.php:preheaderText`, max 130 chars), body content (`Email.php:customHtml`), and plain text variants (`Email.php:plainText`). The GrapesJS builder provides visual drag-and-drop but no content assistance.

**GenAI Enhancement**:
- **Subject line generator**: Given email body content or campaign goal, generate multiple subject line options with predicted open-rate ranking. Integrate into `EmailType.php` form as an "AI Suggest" button next to the subject field.
- **Preheader text generator**: Auto-generate compelling 130-char preheader text that complements the subject line.
- **Email body copywriter**: Generate full email drafts from a brief/prompt, respecting brand voice. Embed into the GrapesJS builder as a content block type.
- **Plain text generator**: Auto-generate `plainText` from `customHtml` with intelligent formatting (not just HTML stripping).
- **Tone/style rewriter**: Rewrite existing copy to be more formal, casual, urgent, etc.

**Impact**: HIGH — Email creation is the most time-consuming daily task for Mautic users. Every email touches these fields.

**Integration point**: New `AiContentService` injected into `GrapesJsBuilderBundle` and `EmailBundle/Form/Type/EmailType.php`. API endpoint for async generation.

---

### 2. Intelligent A/B Test Variant Generation & Analysis

**Where in the code**: `Email.php:variantParent`, `variantChildren`, `variantSettings`, `VariantEntityTrait`

**Current state**: Marketers manually create each A/B test variant. They pick what to change (subject, content, send time) and write each variant by hand. The system tracks `variantSentCount` and `variantReadCount`, but interpretation is manual.

**GenAI Enhancement**:
- **Auto-variant generation**: Given a base email, automatically generate N variants with strategic differences (different subject angles, CTAs, tone, length). Grounded in best practices.
- **Variant analysis & insights**: After test completion, generate natural-language analysis: "Variant B outperformed by 23% — its question-based subject line and shorter body drove higher engagement among the 25-34 segment."
- **Continuous optimization recommendations**: Analyze historical variant performance across campaigns to surface patterns: "Emails with personalized subject lines consistently outperform generic ones by 18% for your audience."

**Impact**: HIGH — A/B testing is underutilized because variant creation is tedious. Automation would multiply usage.

**Integration point**: Extend `EmailModel.php` variant creation flow. New `AiVariantService`.

---

### 3. AI-Enhanced Segmentation & Audience Discovery

**Where in the code**: `LeadBundle/Segment/`, `ContactSegmentService.php`, `ContactSegmentFilterFactory.php`, `ContactSegmentQueryBuilder.php`

**Current state**: Segments are built with manual filter rules (field comparisons, behavioral triggers). The filter system supports operators like eq, gt, lt, like, contains, etc. (`OperatorOptions.php`). Marketers must know which fields to filter and what thresholds to set.

**GenAI Enhancement**:
- **Natural language segment builder**: "Show me contacts who opened an email in the last 30 days but didn't click any links" → translates to segment filter rules. Integrates with `ContactSegmentFilterFactory`.
- **Segment suggestion engine**: Analyze contact data patterns and suggest high-value segments: "You have 2,340 contacts who visit your pricing page weekly but haven't been contacted — create a segment?"
- **Lookalike audience generation**: Given a high-performing segment, identify similar contacts not yet in any segment based on behavioral and demographic patterns.
- **Segment health analysis**: "This segment has grown 340% in 30 days — 89% of new members came from Form X. Consider splitting by engagement level."

**Impact**: HIGH — Segmentation quality directly determines campaign effectiveness. Most users under-segment.

**Integration point**: New `AiSegmentAdvisor` service. NLP parser that outputs `ContactSegmentFilterCrate` arrays.

---

### 4. Predictive Lead Scoring (Replace Static Points)

**Where in the code**: `PointBundle/Entity/Point.php`, `TriggerEvent.php`, `Lead.php:points`

**Current state**: Lead scoring is entirely rule-based. Admins manually assign point values to actions (e.g., "email open = +5 points", "form submit = +20 points") via `Point` entities. Triggers fire when a threshold is reached (`Trigger.php`). There is no learning or adaptation — a page visit on a pricing page scores the same as one on the blog.

**GenAI Enhancement**:
- **Predictive scoring model**: Train on historical conversion data to predict lead-to-customer probability. Replace static points with ML-derived scores.
- **Score explanation**: For each contact, generate a natural-language explanation: "Score: 87/100. This contact has visited the pricing page 4 times this week, downloaded 2 whitepapers, and matches the profile of your top-converting segment."
- **Scoring rule recommendations**: "Your 'email open' action gives +5 points, but analysis shows email opens have 0.02 correlation with conversion. Consider increasing 'pricing page visit' from +10 to +25."
- **Anomaly detection**: Flag contacts with unusual score trajectories for sales review.

**Impact**: HIGH — Accurate scoring is critical for sales-marketing alignment. Static scoring is the #1 complaint.

**Integration point**: New `PredictiveScoreService` that supplements or replaces the `PointBundle` calculation engine.

---

### 5. Campaign Builder AI Assistant

**Where in the code**: `CampaignBundle/Entity/Event.php` (types: `TYPE_ACTION`, `TYPE_CONDITION`, `TYPE_DECISION`), `Campaign.php:canvasSettings`

**Current state**: Campaigns are manually built as trees of events. Each event has a type (action/condition/decision), trigger mode (immediate/interval/date/optimized), and channel. Marketers drag-and-drop to build flows. The `canvasSettings` stores the visual layout. This requires significant marketing automation expertise.

**GenAI Enhancement**:
- **Campaign template generator**: "I want a re-engagement campaign for contacts who haven't opened emails in 60 days" → generates a complete campaign tree with events, timing, and content placeholders.
- **Campaign optimizer**: Analyze existing campaign performance and suggest structural changes: "Add a condition check after email #2 — 67% of non-openers convert after a text message reminder."
- **Natural language campaign builder**: Describe desired flow in plain language → AI generates the event tree with appropriate action types, conditions, and timing intervals.
- **Campaign conflict detector**: "This campaign overlaps with Campaign X — 1,200 contacts will receive both. Consider adding a suppression condition."

**Impact**: VERY HIGH — Campaign building is the most complex feature. Lowering the barrier enables more users to create effective automated workflows.

**Integration point**: New `AiCampaignAssistant` service. Generates `Event` entity arrays with proper parent-child relationships and `canvasSettings`.

---

### 6. Intelligent Send-Time Optimization (Upgrade Existing)

**Where in the code**: `LeadBundle/Services/PeakInteractionTimer.php`, `CampaignBundle/Executioner/Scheduler/Mode/Optimized.php`

**Current state**: Mautic has a basic send-time optimization system (`TRIGGER_MODE_OPTIMIZED`). The `PeakInteractionTimer` collects the last 50 interactions (email reads, page hits, form submissions) from the past 60 days, calculates the **statistical median hour** as "optimal time", and picks the top 3 most frequent days. This is a simple frequency analysis — no ML, no external signals, no multi-variate analysis.

Limitations:
- Only considers 3 interaction types
- Uses simple median (not weighted by recency or engagement depth)
- No seasonality, no day-of-month patterns
- No consideration of email content type or urgency
- Falls back to hardcoded defaults (Tue/Mon/Thu, 9AM-12PM) with < 5 interactions

**GenAI Enhancement**:
- **Multi-signal ML model**: Incorporate additional signals — purchase history, website session duration, industry benchmarks, timezone patterns, device type, content type.
- **Recency-weighted optimization**: Recent interactions should weigh more than 60-day-old ones.
- **Content-aware timing**: Promotional emails may perform best at different times than educational newsletters.
- **Continuous learning**: Update models as new interaction data arrives, not just from cached snapshots.
- **Cohort-based fallback**: When individual data is sparse (< 5 interactions), use cohort-level models (similar contacts) instead of hardcoded defaults.

**Impact**: MEDIUM-HIGH — The infrastructure already exists. Enhancement is incremental but measurable in open rates.

**Integration point**: Replace `calculateOptimalTime()` and `calculateOptimalDays()` in `PeakInteractionTimer.php` with an ML-backed prediction. Keep the same `ScheduleModeInterface` contract.

---

### 7. AI-Powered Dynamic Content Personalization

**Where in the code**: `DynamicContentBundle/`, `DynamicContent.php:filters`, `DynamicContentLeadData.php`

**Current state**: Dynamic content blocks allow displaying different HTML/text to different contacts based on filter rules. Marketers must manually write every content variant and define targeting rules. The `FiltersEntityTrait` handles condition evaluation. Variants are tracked via `Stat` entities.

**GenAI Enhancement**:
- **Auto-personalization**: Given a base content block, generate personalized variants for different segments automatically: "Professional tone for enterprise contacts, casual for SMB."
- **Real-time content generation**: Instead of pre-authored static variants, generate content at render-time based on the contact's profile, behavior, and stage.
- **Product recommendation blocks**: For e-commerce integrations, generate personalized product descriptions and recommendations within dynamic content blocks.
- **Content performance advisor**: "Variant A is shown to 80% of contacts but has the lowest CTR. Consider creating a new variant targeting the 25-34 age group."

**Impact**: MEDIUM-HIGH — Dynamic content is powerful but labor-intensive. AI generation removes the bottleneck of writing N variants.

**Integration point**: New `AiDynamicContentGenerator` that plugs into the DynamicContent rendering pipeline.

---

### 8. Conversational Report Builder & Insights Narrator

**Where in the code**: `ReportBundle/`, `DashboardBundle/`

**Current state**: Reports are built by selecting columns, filters, and sort orders from predefined data sources. The builder supports complex operators (eq, gt, contains, etc.) and channel-specific columns. Dashboards show widgets with metrics. All interpretation is manual.

**GenAI Enhancement**:
- **Natural language report builder**: "Show me email performance by segment for the last quarter" → auto-configures report columns, filters, and grouping.
- **Automated insights narration**: After report generation, produce a natural-language summary: "Email open rates declined 12% month-over-month, primarily driven by the 'Newsletter' segment. However, click-through rates improved 8%, suggesting content quality improvements are working."
- **Anomaly alerts**: "Campaign X engagement dropped 45% this week compared to the 4-week average. The drop correlates with a subject line change on Tuesday."
- **Dashboard AI widget**: A new dashboard widget that surfaces the most important insights across all channels without manual configuration.

**Impact**: MEDIUM — Saves analysis time and makes data accessible to non-analytical marketers.

**Integration point**: New `AiReportService`. NLP parser that generates report configurations. Post-processing layer that narrates `ReportBuilder` output.

---

### 9. Form & Landing Page Optimization

**Where in the code**: `FormBundle/`, `PageBundle/`, form fields and submission tracking

**Current state**: Forms are manually designed with fields, validation, and submission actions. Landing pages are built with GrapesJS. No conversion optimization intelligence exists.

**GenAI Enhancement**:
- **Form copy generator**: Generate field labels, placeholder text, submit button copy, and error messages that are conversion-optimized.
- **Landing page copywriter**: Generate headline, subheadline, body copy, and CTA text from a product brief.
- **Conversion optimization suggestions**: "Your form has 12 fields — forms with 5-7 fields convert 34% better in your industry. Consider removing: Company Size, Job Title, Fax."
- **Progressive profiling advisor**: Suggest which fields to ask on first vs. subsequent visits based on contact data completeness.

**Impact**: MEDIUM — Forms and pages are critical conversion points but changed less frequently than emails.

**Integration point**: Extend `FormType.php` and GrapesJS builder with AI assistant panels.

---

### 10. Contact Data Enrichment & Intelligent Deduplication

**Where in the code**: `LeadBundle/Entity/Lead.php` (50+ fields), `MauticClearbitBundle`, `MauticFullContactBundle`, dedup logic

**Current state**: Contact enrichment relies on two third-party plugins (Clearbit, FullContact) for data lookup. Deduplication is field-match based. Contact merging is manual.

**GenAI Enhancement**:
- **Intelligent deduplication**: Use fuzzy matching and entity resolution to identify duplicates beyond exact field matches: "John Smith" at "j.smith@acme.com" and "Jonathan Smith" at "jonathan@acme.com" with the same company.
- **Data quality scoring**: Score each contact's data completeness and accuracy. Flag suspicious data: "This contact's timezone is UTC but their IP geolocates to Tokyo."
- **Auto-enrichment from interactions**: Infer contact attributes from behavioral data: "This contact consistently reads DevOps content and visits the API documentation — likely a technical decision-maker."
- **Natural language contact summary**: For sales handoff, generate a brief: "Marketing-qualified lead. Engaged 14 times over 3 weeks. Primary interests: enterprise pricing, API integrations. Best contact time: Tuesday mornings."

**Impact**: MEDIUM — Improves data quality which cascades to better segmentation and scoring.

**Integration point**: New `AiContactIntelligence` service integrated into `LeadModel.php`.

---

## Architecture Recommendation

### Proposed GenAI Integration Layer

```
┌─────────────────────────────────────────────────┐
│                  Mautic Core                     │
│  ┌───────────┐ ┌───────────┐ ┌───────────────┐  │
│  │EmailBundle│ │CampaignB. │ │LeadBundle     │  │
│  │           │ │           │ │               │  │
│  │ Subject   │ │ Event Tree│ │ Segments      │  │
│  │ Body Copy │ │ Timing    │ │ Scoring       │  │
│  │ Variants  │ │ Decisions │ │ Contact Data  │  │
│  └─────┬─────┘ └─────┬─────┘ └───────┬───────┘  │
│        │              │               │          │
│  ┌─────▼──────────────▼───────────────▼───────┐  │
│  │         NEW: AiBundle (Plugin)             │  │
│  │                                            │  │
│  │  ┌─────────────────────────────────────┐   │  │
│  │  │ AiProviderInterface                 │   │  │
│  │  │  ├── OpenAiProvider                 │   │  │
│  │  │  ├── AnthropicProvider              │   │  │
│  │  │  ├── OllamaProvider (self-hosted)   │   │  │
│  │  │  └── CustomProvider                 │   │  │
│  │  └─────────────────────────────────────┘   │  │
│  │                                            │  │
│  │  Services:                                 │  │
│  │  ├── AiContentService (emails, pages)      │  │
│  │  ├── AiSegmentAdvisor (segment NLP)        │  │
│  │  ├── AiCampaignAssistant (flow builder)    │  │
│  │  ├── AiScoringService (predictive)         │  │
│  │  ├── AiReportNarrator (insights)           │  │
│  │  └── AiContactIntelligence (enrichment)    │  │
│  └────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────┘
```

### Key Design Principles

1. **Provider-agnostic**: Abstract the LLM behind `AiProviderInterface` so users can choose OpenAI, Anthropic, self-hosted Ollama, or any provider. Configurable in `ConfigBundle`.

2. **Plugin architecture**: Ship as `MauticAiBundle` in `plugins/` to keep the core clean and make the feature optional.

3. **Async-first**: All generation calls should be async (via `MessengerBundle`/queue) to avoid blocking the UI. Return results via SSE or polling.

4. **Privacy-aware**: Allow admins to configure what contact data can be sent to external LLMs. Support on-premise models for GDPR compliance.

5. **Prompt template system**: Store prompts as configurable templates so admins can customize AI behavior, brand voice, and language.

---

## Prioritized Implementation Roadmap

| Priority | Opportunity | Effort | Impact | Dependencies |
|----------|------------|--------|--------|-------------|
| **P0** | Email content generation (subject, body, preheader) | Medium | Very High | AiBundle core, provider integration |
| **P0** | Natural language segment builder | Medium | Very High | AiBundle core, segment filter parser |
| **P1** | A/B variant auto-generation | Low | High | Email content generation (P0) |
| **P1** | Campaign builder assistant | High | Very High | AiBundle core |
| **P1** | Report insights narrator | Medium | Medium-High | AiBundle core |
| **P2** | Predictive lead scoring | High | High | ML pipeline, training data |
| **P2** | Send-time optimization upgrade | Low | Medium-High | Extend existing PeakInteractionTimer |
| **P2** | Dynamic content auto-personalization | Medium | Medium-High | Email content generation (P0) |
| **P3** | Form/landing page optimization | Low | Medium | AiBundle core |
| **P3** | Contact intelligence & dedup | Medium | Medium | AiBundle core |

---

## Conclusion

Mautic's current codebase is **100% rule-based** with no AI/ML features (the only quasi-intelligent feature is the basic `PeakInteractionTimer` median calculator). This represents a massive opportunity: every content creation, decision-making, and analysis workflow in the platform can be enhanced with GenAI.

The highest-impact opportunities are in **content generation** (where marketers spend the most time) and **intelligent segmentation/campaign building** (where the expertise barrier is highest). A provider-agnostic `AiBundle` plugin would be the ideal architectural approach, keeping the core clean while enabling transformative capabilities.
