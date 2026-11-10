# design.md — SIWES Presentation: Microfinance Bank (MFB) Core System

This file is the single source of truth for the look, structure and tone of the deck. Follow it exactly. If something is not specified here, choose the simplest option that stays consistent with the rest of the file.

---

## 1. Purpose and audience

- **What:** An end-of-SIWES (Student Industrial Work Experience Scheme) presentation of a microfinance bank core system I designed and built.
- **Audience:** Department SIWES coordinator, lecturers, and fellow students. Mixed technical level. Some know accounting, most do not.
- **Goal:** Show what I did, what I learned, and that the system is technically sound. Not a sales pitch, not a code walkthrough.
- **Length:** 12–14 slides, roughly 10–15 minutes. One idea per slide.
- **Tone:** Confident, plain English, professional. Explain banking terms in one short line the first time they appear.

---

## 2. Visual identity

Feel: trustworthy banking, modern, calm. Not flashy, not corporate-stock-photo.

### Colour tokens (light theme only)

| Token             | Hex       | Use                                                              |
| ----------------- | --------- | ---------------------------------------------------------------- |
| `--bg`            | `#F7F9FC` | Main slide background (soft off-white)                           |
| `--surface`       | `#FFFFFF` | Cards, panels, diagram boxes, screenshot frames                  |
| `--surface-2`     | `#EEF2F8` | Table stripes, secondary panels, image placeholders              |
| `--border`        | `#DDE4EE` | Hairlines, card borders                                          |
| `--brand`         | `#12305C` | Navy: title slide band, headings on emphasis slides, header bars |
| `--text`          | `#0B1B33` | Primary text                                                     |
| `--text-muted`    | `#5B6B84` | Captions, labels, footers                                        |
| `--accent`        | `#0B8A5F` | Primary accent (emerald): key numbers, highlights, active states |
| `--accent-soft`   | `#E3F5EE` | Tinted background behind accent elements                         |
| `--accent-2`      | `#B7791F` | Secondary accent (gold): "key decision" callouts                 |
| `--accent-2-soft` | `#FBF1DD` | Tinted background behind gold callouts                           |
| `--danger`        | `#C73E3E` | Challenges / problems only                                       |
| `--danger-soft`   | `#FBE7E7` | Tinted background behind challenge tags                          |

Rules:

- **Light theme only. Do not build a dark mode.** Backgrounds are `--bg` or `--surface`; navy (`--brand`) is used only for the title slide band, the closing slide and thin header bars.
- Use the accent sparingly: one accent element per slide is enough.
- Body text must have a contrast ratio of at least 4.5:1 against its background.
- Shadows: at most `0 1px 2px rgba(11,27,51,0.06), 0 8px 24px rgba(11,27,51,0.06)` on cards and screenshot frames.

### Typography

- **Headings:** `Plus Jakarta Sans` (700/800), fallback `Inter, system-ui, sans-serif`
- **Body:** `Inter` (400/500), fallback `system-ui, sans-serif`
- **Numbers, code, account codes:** `JetBrains Mono`, fallback `ui-monospace, monospace`
- Scale (at 1920×1080): Slide title 56px, section label 18px uppercase with letter-spacing 0.12em, body 28px, caption 20px. Never go below 20px.
- Max 6 lines of body text per slide. Max 12 words per bullet.

### Layout

- Canvas: 16:9, designed at 1920×1080, scaled to fit the viewport.
- Safe margin: 96px on all sides.
- 12-column grid, 24px gutters.
- Corner radius 16px on cards, 999px on pills/tags.
- Cards: `--surface` background, 1px `--border`, no heavy shadows.
- Footer on every slide except the title: left "SIWES Presentation · [Name]", right slide number. Muted colour, 18px.
- Generous whitespace. If a slide feels full, split it.

### Motion

- Subtle only: 250ms fade/slide-up between slides, staggered 80ms fade-in for bullets.
- No bouncing, spinning or looping animations.
- Respect `prefers-reduced-motion`.

---

## 3. Slide structure

Placeholders in `[BRACKETS]` must be filled from the content I provide, never invented. If a detail is missing, leave a visible `[TO FILL]` marker.

| #   | Slide                                | Content                                                                                                                                          | Visual                                                                                                                                               |
| --- | ------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | **Title**                            | Project title, my name, matric no., department, institution, organisation attached, SIWES period                                                 | Navy `--brand` band on the left with white title text; on the right a hero image (app dashboard screenshot or a clean abstract banking illustration) |
| 2   | **About the organisation**           | Who they are, what they do, my unit/role                                                                                                         | Simple card + role pill                                                                                                                              |
| 3   | **SIWES objectives and my scope**    | Why SIWES exists (1 line), what I was assigned                                                                                                   | Two-column: Objectives / My scope                                                                                                                    |
| 4   | **The problem**                      | What a microfinance bank needs from software and why spreadsheets/manual books fail                                                              | 3 problem cards                                                                                                                                      |
| 5   | **The solution: MFB Core System**    | One-sentence description and the main modules                                                                                                    | Module tiles (see §4)                                                                                                                                |
| 6   | **System architecture**              | Layers: users/roles, application, database, accounting engine                                                                                    | Layered diagram                                                                                                                                      |
| 7   | **Accounting backbone**              | Chart of accounts, general ledger, subledgers, control accounts (customer balances sit under control accounts, not as individual chart accounts) | Hierarchy diagram: GL → control accounts → subledgers                                                                                                |
| 8   | **How a transaction flows**          | Deposit/transfer → journal entry (debit/credit) → ledger → balance updated                                                                       | Horizontal flow diagram with a small worked example in a mono-font table                                                                             |
| 9   | **Products: deposits and loans**     | Deposit products (interest accrual), loan lifecycle, interest receivable vs interest income                                                      | Two-panel comparison                                                                                                                                 |
| 10  | **End-of-day: "Advance Day"**        | Single atomic action that moves the business date, with pre-condition checks and logging                                                         | Checklist → button → new date timeline                                                                                                               |
| 11  | **Key design decisions**             | e.g. balance_after on ledger entries, display balance vs resolved balance, simulated external transfer (no live NIBSS/NIP)                       | 3 gold-accent decision cards: Decision / Why / Trade-off                                                                                             |
| 12  | **Demo / screenshots**               | 3–4 screens of the real app                                                                                                                      | Real app screenshots in ScreenshotFrames (see §5b), caption under each                                                                               |
| 13  | **Challenges and how I solved them** | 3 challenges, each with its fix                                                                                                                  | Red-tag challenge → green-tag fix                                                                                                                    |
| 14  | **Skills gained and conclusion**     | Technical + professional skills, what I would improve, recommendations                                                                           | Two columns + closing line                                                                                                                           |
| 15  | **Thank you / Questions**            | Contact details                                                                                                                                  | Minimal                                                                                                                                              |

If time is short, merge 4 into 5 and 15 into 14 for a 13-slide deck.

---

## 4. Components

Build these once and reuse them.

- **SectionLabel**: small uppercase muted label above the title (e.g. `ACCOUNTING BACKBONE`).
- **Card**: surface container with optional icon, title, 1–2 lines of text.
- **ModuleTile**: icon + module name + one-line purpose. Used on slide 5.
- **FlowDiagram**: nodes (rounded rectangles) joined by arrows. Left to right, max 5 nodes. Active node uses `--accent` outline.
- **LayerDiagram**: stacked horizontal bars, top = users, bottom = database.
- **LedgerTable**: monospace table for debit/credit examples. Columns: Account, Debit, Credit. Totals row must balance. Amounts right-aligned, formatted `₦1,250,000.00`.
- **DecisionCard**: gold left border, three labelled lines: Decision / Why / Trade-off.
- **Tag**: pill; variants `challenge` (danger), `fix` (accent), `info` (muted).
- **ScreenshotFrame**: rounded 12px border, 1px `--border`, caption below in muted text.
- **ProgressBar**: thin 4px bar at the very top showing slide progress.

Diagrams must be drawn as inline SVG or CSS, not as images, so they stay sharp and match the theme.

---

## 5. Content rules

- Currency is always Naira: `₦`. Use realistic but obviously sample figures, never real customer data.
- Define on first use, in one line: _general ledger_, _subledger_, _control account_, _accrual_, _interest receivable_, _end-of-day (EOD)_.
- Prefer a diagram or a table over a paragraph.
- No code blocks longer than 8 lines. Show code only if it explains a concept (e.g. one balance query).
- Icons: Lucide (outline, 1.75 stroke). Images follow §5b.
- Every slide title is a statement, not a topic. Example: "Every transaction becomes a balanced journal entry" instead of "Transactions".
- Do not claim features that are not built. If something is simulated (e.g. interbank transfers), say "simulated" on the slide.

---

## 5b. Images and screenshots

Images are required: at least **6 slides** must carry a real image.

- **App screenshots** (from `/screenshots`): used on slide 12 (3–4 screens) and as supporting visuals on slides 5, 8, 9 and 10 where a real screen explains the point (e.g. the Advance Day screen on slide 10, a journal entry screen on slide 8). Use every file in the folder at least once. Caption each with what it shows, e.g. "Customer account statement".
- **ScreenshotFrame:** white `--surface` card, 1px `--border`, 12px radius, soft shadow, 16px padding, optional slim browser bar (three small grey dots) on top. Keep the original aspect ratio, `object-fit: contain`, never stretch or crop away important UI.
- **Highlighting:** where useful, add a numbered accent circle (1, 2, 3) over the screenshot and explain each number in a short legend beside it.
- **Privacy:** blur or replace any real customer names, account numbers or phone numbers in screenshots with sample data. Flag any screenshot that appears to contain real data.
- **Supporting images:** if a slide has no screenshot, a clean flat illustration or photo is allowed (organisation building, team, office) only if I supply it in `/images`. Do not use random stock photos or AI-looking decorative art. Where I have not supplied one, use a labelled placeholder box `[IMAGE: what goes here]` in `--surface-2`.
- **Organisation logo:** place on slide 2 and the title slide if supplied at `/images/logo.*`.
- **Sizing:** images should fill at least 40% of the slide width when they are the main visual. Text beside an image is limited to 3 short bullets.
- **Alt text** is required on every image.

---

## 6. Speaker notes

- Every slide has speaker notes of 2–4 sentences, written in first person, spoken-English style.
- Notes include a transition sentence into the next slide.
- Notes are visible in a presenter view (toggle with `P`) and exported as a separate `speaker-notes.md`.

---

## 7. Interaction and output

- Single self-contained deck (one HTML file, or the format the main prompt specifies).
- Keyboard: `→` / `Space` next, `←` previous, `F` fullscreen, `P` presenter notes, `Esc` exit.
- Touch: swipe left/right.
- Print stylesheet: one slide per landscape page, same light theme, so it exports cleanly to PDF.
- Works offline. Images are loaded from the local `/screenshots` and `/images` folders (or embedded), never hotlinked. Fonts may load from Google Fonts with local fallbacks.

---

## 8. Accessibility

- Semantic headings (one `h1` per slide).
- Alt text on every screenshot.
- Never convey meaning by colour alone (pair with a label or icon).
- Minimum click/touch target 44px.

---

## 9. Definition of done

- [ ] All slides from §3 present, in order, with no `Lorem ipsum`
- [ ] Every placeholder either filled or clearly marked `[TO FILL]`
- [ ] No text overflow at 1920×1080 or at 1280×720
- [ ] Ledger examples balance (total debits = total credits)
- [ ] Speaker notes on every slide
- [ ] Prints cleanly to PDF
- [ ] Light theme throughout, no dark mode
- [ ] Every file in `/screenshots` used, captioned, and free of real customer data
- [ ] Reviewed against §5: no unbuilt features claimed
