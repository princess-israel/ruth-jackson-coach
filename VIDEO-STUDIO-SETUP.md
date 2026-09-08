# AI Video Studio — setup

The Video Studio lets you create high quality videos from a text description:
course intros, lesson clips, social reels and adverts. It lives at
**`/video-studio.html`** and is also linked from the Admin sidebar
(**🎬 Video Studio**). Only you can use it, behind your admin password.

Behind the scenes it uses **Replicate** (https://replicate.com), which hosts the
best text-to-video models. It is pay-per-use: you are only billed for the videos
you actually generate, and nothing runs without your API token.

## One-time setup (about 5 minutes)

1. Create a free account at **https://replicate.com** and add a payment method
   (Account → Billing). Video models cost roughly $0.05–$0.50 per short clip
   depending on the engine and resolution.
2. Go to **Account → API tokens** and copy your token (it starts with `r8_`).
3. On the server, open **`api/pesapal/config.php`** (the same file that holds your
   Pesapal keys and admin password) and set:

   ```php
   'replicate_token' => 'r8_your_token_here',
   ```

   `config.php` is never committed to GitHub, so your token stays private.
4. Open **`https://coachruthjackson.com/video-studio.html`**, sign in with your
   admin email and password, and start creating.

That's it. No new database, no extra passwords.

## How to use it

1. **What is this video for?** — pick Course lesson, Social reel, Advert or Brand
   intro. This suggests a starting description and the right shape.
2. **Describe the video** — say who/what is on screen, the setting, the mood and
   any camera movement. Be specific for the best results.
3. **Pick a look** — tap one or more style chips (Cinematic, Corporate clean, …).
4. **Quality engine** — Seedance is a great default. Veo 3 is the highest quality
   and adds sound, but costs more per video.
5. Choose **shape** (16:9 wide, 9:16 vertical for reels, 1:1 square), **length**
   and **resolution**, then press **Generate**.

Rendering takes about 1 to 4 minutes. When it's done you can play it, **Download**
the MP4, or copy the link. Recent videos are remembered on your device — download
the ones you want to keep.

## The engines

| Engine | Best for | Notes |
| --- | --- | --- |
| Seedance 1 Pro | All-round crisp, cinematic clips | 16:9 / 9:16 / 1:1, up to 1080p |
| Hailuo 02 | Lifelike, natural motion | 16:9 only |
| Kling v2.1 Master | Photorealistic people and scenes | 16:9 / 9:16 / 1:1 |
| Google Veo 3 Fast | Premium quality, includes audio | Highest cost per video |

## Notes & troubleshooting

- **"Video service not set up"** — `replicate_token` is missing or blank in
  `config.php`.
- **"The video service rejected the API token"** — the token is wrong or your
  Replicate billing isn't active.
- The studio requires the **live site** (the PHP endpoints don't run in local
  static preview).
- The available engines are fixed in `api/video/_video.php` (`video_models()`)
  so a tampered request can never point your Replicate bill at another model.
