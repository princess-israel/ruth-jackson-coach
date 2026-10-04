# Ruth Jackson carousel style guide (the saved prompt)

Use this every time you build a carousel for Ruth (Instagram, Facebook, TikTok photo posts). Approved on the AI carousel. Other topics follow the same design.

## Paste-ready prompt

Build a 7-slide carousel, 1080x1350 JPG, for the topic: <TOPIC>. Use Ruth's design exactly:

- Colours: navy background (gradient from #14245e to #050a20), white and bright gold (#ffd34d / #ffc21a) only. Font: bold Helvetica/Arial style sans-serif.
- Top-left on every slide: the handle `@timshidigitalswith_ruthjackson` in tiny white text, one line. No Timshi logo, no partner logos, no pills, no bars, no footers.
- Top-right on every slide: the golden full moon (assets/moon-gold.png) with a soft gold glow. Large (290px) on the cover, smaller (210px) on the other slides.
- Bottom-right on every slide: Ruth's illustrated portrait (assets/ruth-cutout.png) on a bright gold disc, running off the bottom edge.
- Slide 1 (cover): the viewer-addressing hook in big white bold text (3 short lines, starts with "You" or "Your"), then one gold line with the cost or promise. Nothing else.
- Slides 2 to 5 (tips): gold circle with the navy number, tip title in big white bold, explanation in bold gold addressed to the viewer ("you"), four small progress dots bottom-left.
- Slide 6 (call to action): "You can learn this with Ruth." in white, the course name in gold, a green "WhatsApp" button (label only, no number), then small white lines for TikTok, Instagram, Facebook and coachruthjackson.com.
- Slide 7 (engagement): "Enjoyed this?" in white and "Do these 3 things:" in gold, then three full-width navy cards. Card 1: Ruth's avatar, "Follow" and a gold "+ Follow" button with a tap cursor. Card 2: "Save for later" with an action bar (heart, comment, share) and a glowing gold bookmark being tapped. Card 3: "Share with a friend" with friend avatars and a gold send button. Handle and moon stay; no swipe cue on this last slide.
- Swipe cue: slides 1 to 6 have a small gold outlined "SWIPE →" pill at the bottom-left (above the progress dots on tip slides). The last slide (7) has none.
- Copy rules: short sentences, speak to the viewer as "you", no em-dashes, no made-up statistics, fake quotes or client names.
- Always post the finished slides in the chat so Ruth can see them.

## How to build

1. Add the topic to `topics.json` (course name, hook as 3 lines, gold line, 4 tips as [title, explanation]).
2. Run `node social/carousel-kit/build.js <topic-slug>` (needs Playwright and Chromium).
3. Output: `social/carousels-v2/<topic-slug>/01.jpg` to `06.jpg`.
4. Check every slide by eye (text overlaps, wrapping) before sending.
5. Write the caption (hook first, same tips, WhatsApp + the three profile links, hashtags).

## Social profile links

- TikTok: https://www.tiktok.com/@timshidigitals
- Instagram: https://www.instagram.com/timshidigitalswith_ruthjackson
- Facebook: https://www.facebook.com/profile.php?id=61560284518376
- WhatsApp: https://wa.me/254729384374

## Status

- AI: built and approved style (carousels-v2/ai).
- Digital Marketing, Cyber Security, Training of Trainers, Graphic Design, Customer Service, Online Safety for Women: to do later, same design.
