/*
 * Zeta Global × emailexpert — 2026/27 Strategic Partnership activation deck.
 * Builds a fully editable 6-slide 16:9 .pptx (native text, shapes, lines only)
 * designed to import cleanly into Google Slides.
 *
 * Run: node build-deck.js
 */

const pptxgen = require("pptxgenjs");

const pptx = new pptxgen();
pptx.layout = "LAYOUT_WIDE"; // 13.333" x 7.5"
pptx.author = "emailexpert";
pptx.company = "Emailexpert UK Ltd";
pptx.title = "Zeta Global × emailexpert — 2026/27 Strategic Partnership";

// ---------------------------------------------------------------- palette --
const INK = "121F35"; // deep editorial navy (dominant)
const INK_SOFT = "1D2E4C"; // card fill on dark slides
const SLATE = "55637A"; // muted body on light
const MUTED_DARK = "9FACC2"; // muted body on dark
const PAPER = "FFFFFF";
const CLOUD = "F3F5F8"; // light neutral card
const LINE_LIGHT = "D9DFE8";
const LINE_DARK = "34486B";
const GOLD_DARKBG = "D9B45B"; // gold accent on dark
const GOLD_TEXT = "8C6A14"; // gold accent readable on white
const WARM_CARD = "FAF6EA"; // additional-commitment card
const WARM_LINE = "E5D9B4";
const WARM_DEEP = "6B5210"; // dark warm text
const CHIP_ADD_FILL = "EFE2BC";

const SERIF = "Cambria"; // headlines / big numbers (maps to Caladea in Google Slides)
const SANS = "Calibri"; // body (maps to Carlito in Google Slides)

const PAGE_W = 13.333;
const MX = 0.6; // outer margin
const CONTENT_W = PAGE_W - 2 * MX;

// ---------------------------------------------------------------- helpers --
function card(slide, x, y, w, h, fill, lineColor) {
  slide.addShape("roundRect", {
    x, y, w, h,
    fill: { color: fill },
    line: lineColor ? { color: lineColor, width: 1 } : { color: fill, width: 0 },
    rectRadius: 0.07,
  });
}

function hline(slide, x, y, w, color, width) {
  slide.addShape("line", {
    x, y, w, h: 0,
    line: { color: color, width: width || 0.75 },
  });
}

function chip(slide, x, y, label, kind) {
  // kind: "contract" | "add"
  const isAdd = kind === "add";
  const w = isAdd ? 4.3 : 1.7;
  slide.addShape("roundRect", {
    x, y, w, h: 0.32,
    fill: { color: isAdd ? CHIP_ADD_FILL : INK },
    line: { color: isAdd ? CHIP_ADD_FILL : INK, width: 0 },
    rectRadius: 0.16,
  });
  slide.addText(label, {
    x, y, w, h: 0.32,
    align: "center", valign: "middle", margin: 0,
    fontFace: SANS, fontSize: 9.5, bold: true, charSpacing: 2,
    color: isAdd ? "5F4A0E" : PAPER,
  });
}

// One consistent treatment for the launch-year qualifier.
function launchTag(slide, x, y, w, text, h) {
  const hh = h || 0.3;
  slide.addShape("roundRect", {
    x, y, w, h: hh,
    fill: { color: CHIP_ADD_FILL },
    line: { color: CHIP_ADD_FILL, width: 0 },
    rectRadius: 0.12,
  });
  slide.addText(text, {
    x: x + 0.14, y, w: w - 0.28, h: hh,
    align: "left", valign: "middle", margin: 0,
    fontFace: SANS, fontSize: 9, italic: true, color: "5F4A0E", lineSpacingMultiple: 1.05,
  });
}

function titleBlock(slide, headline, subhead, dark, size) {
  slide.addText(headline, {
    x: MX, y: 0.42, w: CONTENT_W, h: 0.62, margin: 0,
    fontFace: SERIF, fontSize: size || 28, bold: true,
    color: dark ? PAPER : INK, valign: "middle",
  });
  if (subhead) {
    slide.addText(subhead, {
      x: MX, y: 1.02, w: CONTENT_W, h: 0.34, margin: 0,
      fontFace: SANS, fontSize: 13, color: dark ? MUTED_DARK : SLATE,
      valign: "middle",
    });
  }
}

const BODY = "273349"; // default body text on light backgrounds
function bl(items, size, color, spaceAfter) {
  return items.map((t) => ({
    text: t,
    options: {
      bullet: { code: "2022", indent: 10 },
      breakLine: true,
      paraSpaceAfter: spaceAfter != null ? spaceAfter : 6,
      fontFace: SANS,
      fontSize: size,
      color: color || BODY,
    },
  }));
}

/* ==========================================================================
   SLIDE 1 — Two investments. One joined-up programme.
   ========================================================================== */
{
  const s = pptx.addSlide();
  s.background = { color: INK };

  s.addText("Two investments. One joined-up programme.", {
    x: MX, y: 0.5, w: CONTENT_W, h: 0.7, margin: 0,
    fontFace: SERIF, fontSize: 32, bold: true, color: PAPER, valign: "middle",
  });
  s.addText("Zeta Global × emailexpert · 2026/27 Strategic Partnership", {
    x: MX, y: 1.18, w: CONTENT_W, h: 0.34, margin: 0,
    fontFace: SANS, fontSize: 14, color: MUTED_DARK, valign: "middle",
  });

  const CY = 1.85, CH = 3.42;

  // --- Card 1: Strategic Partner
  card(s, MX, CY, 3.95, CH, INK_SOFT);
  s.addText("STRATEGIC PARTNER", {
    x: MX + 0.3, y: CY + 0.28, w: 3.35, h: 0.3, margin: 0,
    fontFace: SANS, fontSize: 11, bold: true, charSpacing: 2, color: GOLD_DARKBG,
  });
  s.addText("£12,950", {
    x: MX + 0.3, y: CY + 0.62, w: 3.35, h: 0.75, margin: 0,
    fontFace: SERIF, fontSize: 40, bold: true, color: PAPER, valign: "middle",
  });
  s.addText("12-month, always-on relationship", {
    x: MX + 0.3, y: CY + 1.44, w: 3.35, h: 0.32, margin: 0,
    fontFace: SANS, fontSize: 12.5, bold: true, color: PAPER,
  });
  hline(s, MX + 0.3, CY + 1.92, 3.35, LINE_DARK, 1);
  s.addText("Access · visibility · participation · collaboration · curated opportunities", {
    x: MX + 0.3, y: CY + 2.08, w: 3.35, h: 1.1, margin: 0,
    fontFace: SANS, fontSize: 11.5, color: MUTED_DARK, lineSpacingMultiple: 1.15,
  });

  // --- Card 2: FORUM London Platinum
  const C2X = MX + 4.15;
  card(s, C2X, CY, 3.95, CH, INK_SOFT);
  s.addText("FORUM LONDON PLATINUM", {
    x: C2X + 0.3, y: CY + 0.28, w: 3.35, h: 0.3, margin: 0,
    fontFace: SANS, fontSize: 11, bold: true, charSpacing: 2, color: GOLD_DARKBG,
  });
  s.addText("£9,000", {
    x: C2X + 0.3, y: CY + 0.62, w: 3.35, h: 0.75, margin: 0,
    fontFace: SERIF, fontSize: 40, bold: true, color: PAPER, valign: "middle",
  });
  s.addText("incremental London investment", {
    x: C2X + 0.3, y: CY + 1.44, w: 3.35, h: 0.32, margin: 0,
    fontFace: SANS, fontSize: 12.5, bold: true, color: PAPER,
  });
  hline(s, C2X + 0.3, CY + 1.92, 3.35, LINE_DARK, 1);
  s.addText([
    { text: "£14,950 Platinum package", options: { breakLine: true, paraSpaceAfter: 4 } },
    { text: "less £5,950 Strategic Partner credit", options: { breakLine: true, paraSpaceAfter: 8 } },
    { text: "16–17 November 2026 · St Mary's Marylebone", options: { breakLine: false } },
  ], {
    x: C2X + 0.3, y: CY + 2.08, w: 3.35, h: 1.15, margin: 0,
    fontFace: SANS, fontSize: 11.5, color: MUTED_DARK, valign: "top",
  });

  // --- Card 3: totals (stacked)
  const C3X = MX + 8.3, C3W = CONTENT_W - 8.3;
  card(s, C3X, CY, C3W, 1.62, INK_SOFT);
  s.addText("ZETA TOTAL COMMITTED", {
    x: C3X + 0.3, y: CY + 0.22, w: C3W - 0.6, h: 0.28, margin: 0,
    fontFace: SANS, fontSize: 11, bold: true, charSpacing: 2, color: MUTED_DARK,
  });
  s.addText("£21,950", {
    x: C3X + 0.3, y: CY + 0.5, w: C3W - 0.6, h: 0.9, margin: 0,
    fontFace: SERIF, fontSize: 40, bold: true, color: PAPER, valign: "middle",
  });

  card(s, C3X, CY + 1.8, C3W, 1.62, INK_SOFT);
  s.addText("ADDITIONAL 2026 VALUE ADDED BY EMAILEXPERT", {
    x: C3X + 0.3, y: CY + 1.98, w: C3W - 0.6, h: 0.45, margin: 0,
    fontFace: SANS, fontSize: 10.5, bold: true, charSpacing: 1, color: GOLD_DARKBG,
  });
  s.addText("£11,825+", {
    x: C3X + 0.3, y: CY + 2.34, w: C3W - 0.6, h: 0.86, margin: 0,
    fontFace: SERIF, fontSize: 40, bold: true, color: GOLD_DARKBG, valign: "middle",
  });

  // explanatory line
  s.addText(
    "£9,875 in additional qualifying-brand delegate places + £1,950 dedicated co-branded registration experience. " +
    "No monetary value has been attributed to research, targeted outreach, founder time or facilitated introductions.",
    {
      x: MX, y: 5.5, w: CONTENT_W, h: 0.55, margin: 0,
      fontFace: SANS, fontSize: 10.5, color: MUTED_DARK, lineSpacingMultiple: 1.15,
    }
  );

  hline(s, MX, 6.28, CONTENT_W, LINE_DARK, 1);
  s.addText(
    "The contracts establish the foundation. The activation plan shows how emailexpert intends to make that investment perform.",
    {
      x: MX, y: 6.48, w: CONTENT_W, h: 0.6, margin: 0,
      fontFace: SERIF, fontSize: 15, italic: true, color: PAPER, valign: "middle",
    }
  );

  s.addNotes(
    "The deal is agreed — this is an activation plan, not a pitch. Open by separating the two investments: " +
    "the 12-month Strategic Partnership (£12,950) and FORUM London Platinum (£9,000 incremental after the £5,950 " +
    "membership credit against the £14,950 package). Zeta total committed: £21,950. Separately, emailexpert is adding " +
    "£11,825+ of directly attributable 2026 value — only the delegate places (up to £9,875) and the registration " +
    "experience (£1,950) are monetised; research, founder outreach and introductions are deliberately not priced " +
    "(that is what the plus sign communicates). Do not present a combined 'total package value'."
  );
}

/* ==========================================================================
   SLIDE 2 — The 12-month Strategic Partnership
   ========================================================================== */
{
  const s = pptx.addSlide();
  s.background = { color: PAPER };
  titleBlock(s, "The 12-month Strategic Partnership", "What Zeta can rely on — and what emailexpert is adding");

  const LX = MX, LW = 6.05, RX = MX + 6.3, RW = CONTENT_W - 6.3;
  chip(s, LX, 1.5, "CONTRACTUAL", "contract");
  chip(s, RX, 1.5, "ADDITIONAL EMAILEXPERT COMMITMENT", "add");

  const CY = 1.98, CH = 4.72;

  // ------- left: contractual
  card(s, LX, CY, LW, CH, CLOUD);
  s.addText([
    { text: "9 event tickets", options: { fontSize: 15, bold: true, color: INK, breakLine: true, paraSpaceAfter: 1 } },
    { text: "across the membership term", options: { fontSize: 11.5, color: BODY } },
  ], {
    x: LX + 0.3, y: CY + 0.2, w: 3.9, h: 0.62, margin: 0, fontFace: SANS, valign: "top",
  });
  s.addText("£3,555", {
    x: LX + LW - 1.75, y: CY + 0.16, w: 1.45, h: 0.5, margin: 0,
    fontFace: SERIF, fontSize: 22, bold: true, color: GOLD_TEXT, align: "right", valign: "middle",
  });
  s.addText(
    "Benchmarked conservatively at £395 each. The 4 London delegate places are drawn from these 9; " +
    "5 remain available across the rest of the membership term.",
    {
      x: LX + 0.3, y: CY + 0.88, w: LW - 0.6, h: 0.52, margin: 0,
      fontFace: SANS, fontSize: 10, color: SLATE, lineSpacingMultiple: 1.1,
    }
  );
  hline(s, LX + 0.3, CY + 1.5, LW - 0.6, LINE_LIGHT, 1);
  s.addText(bl([
    "Elevated brand presence across emailexpert events and content",
    "Preferred company and category positioning, with an enhanced profile",
    "Priority consideration for speaking opportunities and panels",
    "Participation in selected private sessions",
    "Access to curated introductions and partnership opportunities",
    "Direct access to the emailexpert team for collaboration and campaign support",
    "Member rates on additional sponsorship, delegate places and services",
  ], 10.5, BODY, 6), {
    x: LX + 0.3, y: CY + 1.64, w: LW - 0.6, h: CH - 1.86, margin: 0, valign: "top",
  });

  // ------- right: additional
  card(s, RX, CY, RW, CH, WARM_CARD, WARM_LINE);
  s.addText("12", {
    x: RX + 0.3, y: CY + 0.18, w: 1.05, h: 0.95, margin: 0,
    fontFace: SERIF, fontSize: 48, bold: true, color: GOLD_TEXT, valign: "middle",
  });
  s.addText(
    "Minimum 12 meaningful partnership activations across the membership year — excluding the London activation.",
    {
      x: RX + 1.45, y: CY + 0.18, w: RW - 1.75, h: 0.95, margin: 0,
      fontFace: SANS, fontSize: 12.5, bold: true, color: INK, valign: "middle", lineSpacingMultiple: 1.1,
    }
  );
  s.addText(bl([
    "Proactive identification of opportunities aligned with Zeta priorities and ICP — emailexpert brings ideas to Zeta rather than waiting for requests",
    "Formats include curated introductions, webinars, private sessions, roundtables, joint programme participation and relevant partner or JV opportunities",
    "Selected discretionary event access where useful and appropriate",
  ], 10.5, BODY, 7), {
    x: RX + 0.3, y: CY + 1.28, w: RW - 0.6, h: 1.85, margin: 0, valign: "top",
  });

  // definition box
  card(s, RX + 0.3, CY + 3.3, RW - 0.6, 1.18, PAPER, WARM_LINE);
  s.addText([
    { text: "Partnership activation", options: { bold: true, color: INK, fontSize: 10.5, breakLine: true, paraSpaceAfter: 3 } },
    { text: "A meaningful opportunity created by emailexpert around an agreed Zeta objective or ICP. It is not necessarily a one-to-one sales introduction.", options: { color: SLATE, fontSize: 10 } },
  ], {
    x: RX + 0.48, y: CY + 3.42, w: RW - 0.96, h: 0.94, margin: 0, fontFace: SANS, valign: "top", lineSpacingMultiple: 1.1,
  });

  launchTag(s, MX, 6.9, 6.7, "Additional commitments apply to the 2026/27 term and are not automatically renewed.");

  s.addNotes(
    "Left column is contractual — present it as what Zeta can rely on, never as goodwill. The 9 tickets carry the only " +
    "attributed value here (£3,555 at the conservative £395 benchmark); the 4 London places come out of the 9, so nothing " +
    "is valued twice. Right column is voluntary: a minimum of 12 meaningful partnership activations across the year, " +
    "excluding London. Emphasise that emailexpert brings ideas proactively. No monetary value is placed on the 12 " +
    "activations. The additional commitments are for the 2026/27 term and do not renew automatically — say this warmly, " +
    "as a launch-year investment in making the relationship succeed."
  );
}

/* ==========================================================================
   SLIDE 3 — London: 50-Organisation Audience-Development Programme
   ========================================================================== */
{
  const s = pptx.addSlide();
  s.background = { color: PAPER };
  titleBlock(s, "London: 50-Organisation Audience-Development Programme", "Two outreach engines working in parallel", false, 24);

  const CY = 1.52, CH = 3.66;
  const LX = MX, LW = 5.95, RX = MX + 6.18, RW = CONTENT_W - 6.18;

  // Zeta engine
  card(s, LX, CY, LW, CH, CLOUD);
  s.addText("ZETA", {
    x: LX + 0.3, y: CY + 0.2, w: LW - 0.6, h: 0.3, margin: 0,
    fontFace: SANS, fontSize: 12, bold: true, charSpacing: 2.5, color: INK,
  });
  hline(s, LX + 0.3, CY + 0.56, LW - 0.6, LINE_LIGHT, 1);
  s.addText(bl([
    "Provides approximately 50 priority organisations — around 25 customers and 25 prospects, if that remains the useful split",
    "Supplies known senior contacts and personas where available",
    "Identifies the 10–12 highest-priority named individuals",
    "Runs relationship-led outreach through its own channels and existing relationships",
    "Directs suitable people towards the dedicated Zeta × FORUM registration experience",
  ], 10.5, BODY, 6), {
    x: LX + 0.3, y: CY + 0.7, w: LW - 0.6, h: CH - 0.9, margin: 0, valign: "top",
  });

  // emailexpert engine
  card(s, RX, CY, RW, CH, CLOUD);
  s.addText("EMAILEXPERT", {
    x: RX + 0.3, y: CY + 0.2, w: RW - 0.6, h: 0.3, margin: 0,
    fontFace: SANS, fontSize: 12, bold: true, charSpacing: 2.5, color: GOLD_TEXT,
  });
  hline(s, RX + 0.3, CY + 0.56, RW - 0.6, LINE_LIGHT, 1);
  s.addText(bl([
    "Researches and maps appropriate senior individuals across all ~50 organisations",
    "Focuses on senior in-house practitioners and decision-makers aligned with the agreed ICP",
    "Runs independent emailexpert-branded outreach, with personalised founder-led outreach to the 10–12 agreed priority targets",
    "As an independent trade publication, community and event organiser, creates a different invitation context from vendor outreach",
    "Reports activity and resulting registrations",
  ], 10.5, BODY, 6), {
    x: RX + 0.3, y: CY + 0.7, w: RW - 0.6, h: CH - 0.9, margin: 0, valign: "top",
  });

  // boundary statement
  card(s, MX, 5.36, CONTENT_W, 0.78, INK);
  s.addText(
    "emailexpert commits to the research and outreach activity. Attendance by any particular individual or organisation — and subsequent commercial interest — cannot be guaranteed.",
    {
      x: MX + 0.3, y: 5.36, w: CONTENT_W - 0.6, h: 0.78, margin: 0,
      fontFace: SANS, fontSize: 12, bold: true, color: PAPER, valign: "middle", lineSpacingMultiple: 1.1,
    }
  );

  s.addText(
    "The underlying research and contact mapping remains an emailexpert working asset. Zeta receives qualifying registrants " +
    "generated through the dedicated sponsored registration route, not emailexpert's underlying prospecting file.",
    {
      x: MX, y: 6.35, w: CONTENT_W, h: 0.6, margin: 0,
      fontFace: SANS, fontSize: 10.5, italic: true, color: SLATE, lineSpacingMultiple: 1.15,
    }
  );

  s.addNotes(
    "Two engines run in parallel. Zeta brings the target list (~50 organisations, ~25 customers / 25 prospects if that " +
    "split stays useful), known contacts, and the 10–12 priority names, and runs relationship-led outreach. emailexpert " +
    "researches and maps senior people across all ~50, runs independent branded outreach, and Andrew personally leads " +
    "outreach to the 10–12 priority targets — the independent publication/community position creates a different " +
    "invitation context from vendor outreach. Commit to activity, not outcomes: attendance cannot be guaranteed. The " +
    "research file remains an emailexpert working asset; Zeta receives registrants via the sponsored route — frame as " +
    "commercially sensible practice, not defensiveness."
  );
}

/* ==========================================================================
   SLIDE 4 — London: Sponsored Brand Invitations
   ========================================================================== */
{
  const s = pptx.addSlide();
  s.background = { color: PAPER };
  titleBlock(s, "London: Sponsored Brand Invitations");

  const CY = 1.28, CH = 5.14;

  // ---- left anchor rail
  const AX = MX, AW = 3.0;
  card(s, AX, CY, AW, CH, INK);
  s.addText("ADDITIONAL DIRECTLY ATTRIBUTABLE LONDON VALUE", {
    x: AX + 0.28, y: CY + 0.28, w: AW - 0.56, h: 0.78, margin: 0,
    fontFace: SANS, fontSize: 10.5, bold: true, charSpacing: 1, color: MUTED_DARK, lineSpacingMultiple: 1.15,
  });
  s.addText("£11,825", {
    x: AX + 0.28, y: CY + 1.14, w: AW - 0.56, h: 0.8, margin: 0,
    fontFace: SERIF, fontSize: 32, bold: true, color: GOLD_DARKBG, valign: "middle",
  });
  hline(s, AX + 0.28, CY + 2.1, AW - 0.56, LINE_DARK, 1);
  s.addText([
    { text: "£9,875", options: { bold: true, color: PAPER } },
    { text: "  up to 25 qualifying delegate places", options: { color: MUTED_DARK, breakLine: true, paraSpaceAfter: 8 } },
    { text: "£1,950", options: { bold: true, color: PAPER } },
    { text: "  dedicated registration experience", options: { color: MUTED_DARK } },
  ], {
    x: AX + 0.28, y: CY + 2.28, w: AW - 0.56, h: 1.3, margin: 0,
    fontFace: SANS, fontSize: 11, valign: "top", lineSpacingMultiple: 1.1,
  });
  s.addText("Additional to Zeta's contracted packages — not a substitute for anything Zeta has bought.", {
    x: AX + 0.28, y: CY + 3.85, w: AW - 0.56, h: 1.0, margin: 0,
    fontFace: SANS, fontSize: 10, italic: true, color: MUTED_DARK, lineSpacingMultiple: 1.15, valign: "bottom",
  });

  // ---- card A
  const A2X = MX + 3.22, A2W = 4.62;
  card(s, A2X, CY, A2W, CH, CLOUD);
  s.addText("A · UP TO 25 QUALIFYING COMPLIMENTARY DELEGATE PLACES", {
    x: A2X + 0.28, y: CY + 0.2, w: A2W - 0.56, h: 0.52, margin: 0,
    fontFace: SANS, fontSize: 10.5, bold: true, charSpacing: 0.5, color: INK, lineSpacingMultiple: 1.15,
  });
  s.addText("Up to 25 × £395", {
    x: A2X + 0.28, y: CY + 0.72, w: A2W - 0.56, h: 0.42, margin: 0,
    fontFace: SERIF, fontSize: 21, bold: true, color: INK, valign: "middle",
  });
  s.addText("Face value: up to £9,875", {
    x: A2X + 0.28, y: CY + 1.14, w: A2W - 0.56, h: 0.3, margin: 0,
    fontFace: SANS, fontSize: 12, bold: true, color: GOLD_TEXT, valign: "middle",
  });
  launchTag(s, A2X + 0.28, CY + 1.52, A2W - 0.56, "2026 launch-year commitment — additional to Zeta's own contracted places", 0.44);
  s.addText(bl([
    "Default of one complimentary delegate per qualifying organisation, with flexibility where emailexpert agrees a larger enterprise client group is appropriate",
    "Named individual · non-transferable",
    "Senior in-house practitioner or decision-maker",
    "Brand or end-user organisation aligned with the agreed Zeta ICP",
    "Vendors, agencies and consultants excluded unless specifically agreed",
    "Zeta may nominate both prospects and existing enterprise customers",
    "Final allocation remains at emailexpert's discretion, to protect the quality and balance of the Forum audience",
  ], 9.5, BODY, 5), {
    x: A2X + 0.28, y: CY + 2.1, w: A2W - 0.56, h: CH - 2.32, margin: 0, valign: "top",
  });

  // ---- card B
  const BX = MX + 8.06, BW = CONTENT_W - 8.06;
  card(s, BX, CY, BW, CH, CLOUD);
  s.addText("B · DEDICATED ZETA × FORUM REGISTRATION EXPERIENCE", {
    x: BX + 0.28, y: CY + 0.2, w: BW - 0.56, h: 0.52, margin: 0,
    fontFace: SANS, fontSize: 10.5, bold: true, charSpacing: 0.5, color: INK, lineSpacingMultiple: 1.15,
  });
  s.addText([
    { text: "Attributed value ", options: { fontFace: SANS, fontSize: 12, bold: true, color: INK } },
    { text: "£1,950", options: { fontFace: SERIF, fontSize: 21, bold: true, color: GOLD_TEXT } },
  ], {
    x: BX + 0.28, y: CY + 0.74, w: BW - 0.56, h: 0.44, margin: 0, valign: "middle",
  });
  s.addText(bl([
    "Co-branded Zeta Global × FORUM London landing page, hosted on thelondonforum.com — both Zeta and emailexpert may drive traffic to it",
    "Clear disclosure above the registration form that the complimentary place is sponsored by Zeta",
    "Registration through this sponsored route shares name, job title, company and business email with Zeta",
    "Clear alternative route to buy a normal Forum ticket for anyone who prefers not to register through the sponsored Zeta route",
    "Complimentary place confirmed only after qualification",
    "Weekly export to one named Zeta recipient",
    "Dietary information, accessibility information and emailexpert marketing preferences remain with emailexpert",
  ], 9.5, BODY, 5), {
    x: BX + 0.28, y: CY + 1.26, w: BW - 0.56, h: CH - 1.5, margin: 0, valign: "top",
  });

  // bottom line
  s.addText([
    { text: "Additional directly attributable London value: ", options: { fontSize: 13, bold: true, color: INK } },
    { text: "£11,825", options: { fontFace: SERIF, fontSize: 16, bold: true, color: GOLD_TEXT } },
    { text: "      2026 launch-year commitment; not an automatically renewing entitlement.", options: { fontSize: 10, italic: true, color: SLATE } },
  ], {
    x: MX, y: 6.62, w: CONTENT_W, h: 0.45, margin: 0, fontFace: SANS, valign: "middle",
  });

  s.addNotes(
    "£11,825 is the anchor: up to 25 qualifying complimentary delegate places (up to £9,875 at the £395 benchmark) plus " +
    "the dedicated co-branded registration experience (£1,950). Walk through qualification briefly — it protects the " +
    "quality and balance of the Forum audience for both sides. These are invitations to the right senior brand-side " +
    "people, not 'leads'. The sponsored route shares name, job title, company and business email with Zeta, with clear " +
    "disclosure and a normal-ticket alternative; dietary, accessibility and marketing preferences stay with emailexpert. " +
    "Launch-year commitment — not an automatically renewing entitlement."
  );
}

/* ==========================================================================
   SLIDE 5 — London: From Attendance to Meaningful Conversations
   ========================================================================== */
{
  const s = pptx.addSlide();
  s.background = { color: PAPER };
  titleBlock(s, "London: From Attendance to Meaningful Conversations");

  const LX = MX, LW = 6.35, RX = MX + 6.6, RW = CONTENT_W - 6.6;
  chip(s, LX, 1.28, "CONTRACTUAL", "contract");
  chip(s, RX, 1.28, "ADDITIONAL EMAILEXPERT COMMITMENT", "add");

  const CY = 1.76, CH = 5.32;

  // ---- left: Platinum package
  card(s, LX, CY, LW, CH, CLOUD);
  s.addText("THE PLATINUM PACKAGE", {
    x: LX + 0.3, y: CY + 0.18, w: LW - 0.6, h: 0.3, margin: 0,
    fontFace: SANS, fontSize: 11, bold: true, charSpacing: 2, color: INK,
  });
  s.addText(bl([
    "One of only two Platinum Partner positions",
    "Premium main-hall exhibitor presence, with additional networking-area branding",
    "Photocall, badge, screen and stage recognition as provided in the agreement",
    "Prominent pre-event, onsite and post-event recognition",
    "20–30 minute programme session",
    "6 observer passes for invited prospects, clients and partners — not additional delegate inventory",
  ], 10.5, BODY, 5), {
    x: LX + 0.3, y: CY + 0.52, w: LW - 0.6, h: 2.25, margin: 0, valign: "top",
  });
  s.addText(
    "The 20–30 minute session can be shaped as a Zeta-led contribution, a Zeta + customer presentation, a practitioner case study, a panel, or another substantive format that suits the programme and audience.",
    {
      x: LX + 0.3, y: CY + 2.86, w: LW - 0.6, h: 0.78, margin: 0,
      fontFace: SANS, fontSize: 10, italic: true, color: SLATE, lineSpacingMultiple: 1.12,
    }
  );
  card(s, LX + 0.3, CY + 3.76, LW - 0.6, 1.36, PAPER, LINE_LIGHT);
  s.addText([
    { text: "Standard delegate directory", options: { bold: true, color: INK, fontSize: 10, breakLine: true, paraSpaceAfter: 3 } },
    { text: "Registered delegates have access to the normal Forum delegate information made available to other attendees — name · job title · organisation — subject to attendee opt-out. No email address, no telephone number, no implied marketing permission. Separate from the Zeta-sponsored landing-page data flow.", options: { color: SLATE, fontSize: 9.5 } },
  ], {
    x: LX + 0.46, y: CY + 3.88, w: LW - 0.92, h: 1.14, margin: 0, fontFace: SANS, valign: "top", lineSpacingMultiple: 1.1,
  });

  // ---- right: additional commitment
  card(s, RX, CY, RW, CH, WARM_CARD, WARM_LINE);
  s.addText("6–9", {
    x: RX + 0.3, y: CY + 0.2, w: 1.35, h: 0.95, margin: 0,
    fontFace: SERIF, fontSize: 44, bold: true, color: GOLD_TEXT, valign: "middle",
  });
  s.addText("facilitated London activations — on top of the wider 12-month partnership commitment", {
    x: RX + 1.75, y: CY + 0.2, w: RW - 2.05, h: 0.95, margin: 0,
    fontFace: SANS, fontSize: 12.5, bold: true, color: INK, valign: "middle", lineSpacingMultiple: 1.1,
  });
  card(s, RX + 0.3, CY + 1.32, RW - 0.6, 1.32, PAPER, WARM_LINE);
  s.addText([
    { text: "London activation", options: { bold: true, color: INK, fontSize: 10.5, breakLine: true, paraSpaceAfter: 3 } },
    { text: "A facilitated introduction or curated conversation between a named Zeta representative and a named qualified attendee, agreed in advance and delivered onsite or in the run-up to the Forum.", options: { color: SLATE, fontSize: 10 } },
  ], {
    x: RX + 0.48, y: CY + 1.44, w: RW - 0.96, h: 1.1, margin: 0, fontFace: SANS, valign: "top", lineSpacingMultiple: 1.1,
  });
  s.addText("A complimentary ticket being accepted does not itself count as an activation.", {
    x: RX + 0.3, y: CY + 2.84, w: RW - 0.6, h: 0.5, margin: 0,
    fontFace: SANS, fontSize: 11, bold: true, color: INK, valign: "middle", lineSpacingMultiple: 1.1,
  });
  hline(s, RX + 0.3, CY + 3.48, RW - 0.6, WARM_LINE, 1);
  s.addText(
    "The 6–9 London activations are additional to the minimum 12 meaningful partnership activations across the wider membership term. London does not consume the annual partnership commitment.",
    {
      x: RX + 0.3, y: CY + 3.62, w: RW - 0.6, h: 1.5, margin: 0,
      fontFace: SANS, fontSize: 10.5, color: BODY, valign: "top", lineSpacingMultiple: 1.15,
    }
  );

  s.addNotes(
    "Keep the contractual Platinum package distinct from the additional London commitment. Observer passes are for " +
    "guests Zeta invites — prospects, clients and partners — not staff tickets, they are not additional delegate " +
    "inventory, and no value is attributed to them. The session is flexible in format but substantive in character. " +
    "On the right: 6–9 facilitated London activations sit on top of the annual 12 — London does not consume the " +
    "partnership commitment — and an accepted complimentary ticket alone never counts as an activation. No monetary " +
    "value is placed on the 6–9 activations. The standard delegate directory (name, job title, organisation, subject " +
    "to opt-out) is separate from the sponsored landing-page data flow."
  );
}

/* ==========================================================================
   SLIDE 6 — Turning the plan into action
   ========================================================================== */
{
  const s = pptx.addSlide();
  s.background = { color: PAPER };
  titleBlock(s, "Turning the plan into action", "What we need from Zeta now");

  const CY = 1.5, CH = 2.62;
  const W3 = (CONTENT_W - 0.5) / 3;

  const cols = [
    {
      head: "AUDIENCE",
      items: [
        "Approximately 50 priority organisations, with known senior contacts and personas where available",
        "The 10–12 highest-priority individuals",
        "Agreed ICP and qualification criteria",
        "Relative sector priority: Retail & Grocery · Financial Services · Travel · enterprise and multi-location restaurant groups",
        "Named Zeta owner for Zeta's own outreach",
      ],
    },
    {
      head: "LANDING PAGE — BEFORE LAUNCH",
      items: [
        "Zeta privacy notice URL",
        "One named Zeta data recipient",
        "Brand assets",
        "Approval of landing-page wording and disclosure",
        "Zeta campaign and outreach owner",
      ],
    },
    {
      head: "PROGRAMME",
      items: [
        "Preferred Zeta speaker",
        "Preferred customer or client participant, if pursuing a joint session",
        "Initial session concept or theme",
      ],
    },
  ];
  cols.forEach((c, i) => {
    const x = MX + i * (W3 + 0.25);
    card(s, x, CY, W3, CH, CLOUD);
    s.addText(c.head, {
      x: x + 0.24, y: CY + 0.16, w: W3 - 0.48, h: 0.28, margin: 0,
      fontFace: SANS, fontSize: 10.5, bold: true, charSpacing: 1.5, color: INK,
    });
    hline(s, x + 0.24, CY + 0.5, W3 - 0.48, LINE_LIGHT, 1);
    s.addText(bl(c.items, 9.5, BODY, 5), {
      x: x + 0.24, y: CY + 0.62, w: W3 - 0.48, h: CH - 0.82, margin: 0, valign: "top",
    });
  });

  // key dates
  const DY = 4.26;
  hline(s, MX, DY - 0.12, CONTENT_W, LINE_LIGHT, 1);
  s.addText([
    { text: "18 September 2026", options: { fontFace: SERIF, fontSize: 13, bold: true, color: INK, breakLine: true, paraSpaceAfter: 2 } },
    { text: "logo and brand assets · speaker name and title · initial session outline · company profile and boilerplate", options: { fontFace: SANS, fontSize: 10, color: SLATE } },
  ], {
    x: MX, y: DY, w: 7.0, h: 0.66, margin: 0, valign: "top", lineSpacingMultiple: 1.08,
  });
  s.addText([
    { text: "30 October 2026", options: { fontFace: SERIF, fontSize: 13, bold: true, color: INK, breakLine: true, paraSpaceAfter: 2 } },
    { text: "final session materials · pass nominations", options: { fontFace: SANS, fontSize: 10, color: SLATE } },
  ], {
    x: MX + 7.5, y: DY, w: CONTENT_W - 7.5, h: 0.66, margin: 0, valign: "top", lineSpacingMultiple: 1.08,
  });

  // additional opportunities
  const AY = 5.34, AH = 1.06;
  s.addText([
    { text: "ADDITIONAL OPPORTUNITIES", options: { fontSize: 10.5, bold: true, charSpacing: 2, color: GOLD_TEXT } },
    { text: "   Separate paid opportunities — not included in the £11,825 value total.", options: { fontSize: 10, italic: true, color: SLATE } },
  ], {
    x: MX, y: AY - 0.36, w: CONTENT_W, h: 0.3, margin: 0, fontFace: SANS, valign: "middle",
  });
  const HW1 = 6.9, HW2 = CONTENT_W - HW1 - 0.25;
  card(s, MX, AY, HW1, AH, WARM_CARD, WARM_LINE);
  s.addText([
    { text: "Zeta-hosted Breakfast", options: { bold: true, color: INK, fontSize: 10.5, breakLine: true, paraSpaceAfter: 2 } },
    { text: "Currently being scoped with Caroline. A high-quality Zeta-hosted experience rather than simply refreshments — dedicated space, catering, teas and coffees, seating and networking format, branding, and a sponsored registration route. Separate investment — options and costs to follow.", options: { color: BODY, fontSize: 9 } },
  ], {
    x: MX + 0.24, y: AY + 0.1, w: HW1 - 0.48, h: AH - 0.2, margin: 0, fontFace: SANS, valign: "top", lineSpacingMultiple: 1.05,
  });
  card(s, MX + HW1 + 0.25, AY, HW2, AH, WARM_CARD, WARM_LINE);
  s.addText([
    { text: "Gala Dinner Partnership", options: { bold: true, color: INK, fontSize: 10.5, breakLine: true, paraSpaceAfter: 2 } },
    { text: "The opportunity to sponsor and host the Gala Dinner as an additional customer and prospect hospitality experience. Separate investment — proposal and pricing to follow.", options: { color: BODY, fontSize: 9 } },
  ], {
    x: MX + HW1 + 0.49, y: AY + 0.1, w: HW2 - 0.48, h: AH - 0.2, margin: 0, fontFace: SANS, valign: "top", lineSpacingMultiple: 1.05,
  });

  // closing band
  s.addShape("rect", { x: 0, y: 6.52, w: PAGE_W, h: 0.98, fill: { color: INK }, line: { color: INK, width: 0 } });
  s.addText(
    "We are not looking to charge Zeta every time we can be helpful. The Strategic Partnership is deliberately designed to create room for collaboration. Where an idea becomes a dedicated hospitality experience, production project or substantial bespoke activation, we will scope it separately and transparently.",
    {
      x: MX, y: 6.58, w: CONTENT_W, h: 0.52, margin: 0,
      fontFace: SANS, fontSize: 10, color: PAPER, valign: "middle", lineSpacingMultiple: 1.1,
    }
  );
  s.addText("The contracts define the foundation. This plan shows how we intend to make the relationship perform.", {
    x: MX, y: 7.1, w: CONTENT_W, h: 0.32, margin: 0,
    fontFace: SERIF, fontSize: 11, italic: true, color: GOLD_DARKBG, valign: "middle",
  });

  s.addNotes(
    "Close with the asks: audience inputs (the ~50 organisations, the 10–12 priority names, ICP and sector priority, a " +
    "named outreach owner), landing-page inputs before launch, and programme inputs. Then the two contractual dates: " +
    "18 September (brand assets, speaker name and title, initial session outline, company profile) and 30 October " +
    "(final session materials, pass nominations). The Breakfast (being scoped with Caroline) and the Gala Dinner are " +
    "separate paid opportunities and sit outside the £11,825 total — introduce them as natural extensions, not an " +
    "upsell. End on the closing statement: we are not metering goodwill; substantial bespoke projects get scoped " +
    "separately and transparently."
  );
}

// ------------------------------------------------------------------ write --
pptx.writeFile({ fileName: "zeta-emailexpert-partnership-2026.pptx" }).then((f) => {
  console.log("Wrote", f);
});
