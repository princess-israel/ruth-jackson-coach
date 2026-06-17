/* ============================================================
   Shared catalog data, Ruth Jackson programs
   ============================================================ */
window.RJ_PROGRAMS = [
  {
    id: "ai-women-entrepreneurs",
    icon: "🤖",
    title: "Artificial Intelligence for Women Entrepreneurs",
    short: "Use AI tools to plan, market and run your business, no tech background needed.",
    price: 79,
    level: "Beginner → Intermediate",
    hours: 8,
    lessons: 24,
    tags: ["AI Tools", "Productivity", "Marketing"],
    outcomes: [
      "Use ChatGPT & AI assistants to write content, plans and proposals",
      "Automate repetitive admin and customer replies",
      "Create marketing copy, product descriptions and social posts with AI",
      "Build a simple AI-powered workflow for your business"
    ],
    summary: "Ruth's flagship program, built on the same Microsoft & ITC-ILO curriculum she is certified to deliver. Learn to put practical AI to work in a real small business this week."
  },
  {
    id: "digital-marketing-social",
    icon: "📣",
    title: "Digital Marketing & Social Media Management",
    short: "Grow a loyal audience and turn followers into paying customers.",
    price: 79,
    level: "Beginner",
    hours: 7,
    lessons: 21,
    tags: ["Social Media", "Branding", "Ads"],
    outcomes: [
      "Build a content plan that posts itself consistently",
      "Design a recognisable brand voice and visual identity",
      "Run affordable, high-converting ads on Meta & TikTok",
      "Measure what works and double down on it"
    ],
    summary: "From zero strategy to a calendar that runs your social presence on autopilot, with templates you keep forever."
  },
  {
    id: "website-development",
    icon: "🌐",
    title: "Build Your Business Website",
    short: "Launch a professional, sales-ready website without writing code.",
    price: 79,
    level: "Beginner",
    hours: 6,
    lessons: 18,
    tags: ["Web", "No-Code", "Conversion"],
    outcomes: [
      "Plan a website that actually converts visitors to buyers",
      "Build and publish using no-code tools",
      "Connect a domain, email and payment buttons",
      "Make it fast and mobile-friendly"
    ],
    summary: "A practical, click-along build. By the end you have a live website you fully own and can update yourself."
  },
  {
    id: "seo-online-visibility",
    icon: "🔎",
    title: "SEO & Online Visibility",
    short: "Get found on Google by the customers already searching for you.",
    price: 79,
    level: "Intermediate",
    hours: 6,
    lessons: 17,
    tags: ["SEO", "Content", "Google"],
    outcomes: [
      "Find the keywords your customers actually type",
      "Write pages and articles that rank",
      "Set up Google Business Profile for local sales",
      "Track rankings and traffic for free"
    ],
    summary: "The exact, no-jargon SEO routine that grows free, compounding traffic month after month."
  },
  {
    id: "data-analysis-growth",
    icon: "📊",
    title: "Data Analysis for Business Growth",
    short: "Read your numbers with confidence and make smarter decisions.",
    price: 79,
    level: "Beginner → Intermediate",
    hours: 7,
    lessons: 20,
    tags: ["Data", "Spreadsheets", "Decisions"],
    outcomes: [
      "Track sales, costs and profit clearly",
      "Build simple dashboards in spreadsheets",
      "Spot trends and stop guessing",
      "Use AI to summarise and explain your data"
    ],
    summary: "Turn the numbers you already have into decisions that grow margin and cash flow."
  },
  {
    id: "ecommerce-selling-online",
    icon: "🛒",
    title: "E-Commerce & Selling Online",
    short: "Set up a store, take payments and ship, start selling this month.",
    price: 79,
    level: "Beginner",
    hours: 7,
    lessons: 19,
    tags: ["E-Commerce", "Payments", "Sales"],
    outcomes: [
      "Choose the right platform for your products",
      "Set up payments, delivery and order tracking",
      "Photograph and list products that sell",
      "Run launch promotions that drive first sales"
    ],
    summary: "Everything between a product idea and your first online order, in one guided path."
  }
];

/* The free, contact-only flagship personal training */
window.RJ_SIGNATURE = {
  id: "customer-service-excellence",
  icon: "💬",
  title: "Customer Service Excellence, Personal Training by Ruth",
  short: "Custom, live training drawn from Ruth's years in banking customer service.",
  level: "All levels · Teams welcome",
  tags: ["Customer Service", "Live & Custom", "For Teams"],
  outcomes: [
    "Turn complaints into loyalty and repeat business",
    "Communication frameworks that calm any situation",
    "Phone, chat and in-person service standards",
    "A service culture your whole team can follow"
  ],
  summary: "This one is personal. Before coaching, Ruth spent years on the front line of banking customer service, it is her strongest craft. She designs each session around your team and your customers. There is no fixed price; reach out and Ruth will tailor a quote to your needs."
};

/* Image maps (so cards/pages show art regardless of the saved catalog data) */
window.RJ_PROGRAM_IMG = {
  "ai-women-entrepreneurs":   "assets/img/program-ai.jpg",
  "digital-marketing-social": "assets/img/program-marketing.jpg",
  "website-development":      "assets/img/program-website.jpg",
  "seo-online-visibility":    "assets/img/program-seo.jpg",
  "data-analysis-growth":     "assets/img/program-data.jpg",
  "ecommerce-selling-online": "assets/img/program-ecommerce.jpg"
};
window.RJ_ARTICLE_IMG = {
  "ai-for-small-business":       "assets/img/article-ai.jpg",
  "customer-service-that-sells": "assets/img/article-customer-service.jpg",
  "women-digital-economy":       "assets/img/article-women.jpg"
};

/* Partner organisations (real WIDB ecosystem) */
window.RJ_PARTNERS = [
  "Microsoft", "Women in Digital Business", "ITC · International Training Centre",
  "International Labour Organization", "Microsoft Elevate", "EY"
];

/* Topics covered in each course (syllabus shown on the course page).
   Kept as a separate map so it survives the admin/server catalog merge. */
window.RJ_PROGRAM_TOPICS = {
  "ai-women-entrepreneurs": [
    "What AI is (and isn't) for a small business",
    "Writing with ChatGPT: content, emails & proposals",
    "AI prompts that get usable results every time",
    "Creating images & graphics with AI tools",
    "Automating admin, replies & scheduling",
    "Building a simple AI workflow for your business",
    "Using AI safely & responsibly",
    "Your 30-day practical AI action plan"
  ],
  "digital-marketing-social": [
    "Knowing your audience & ideal customer",
    "Building a recognisable brand voice & look",
    "A content calendar that posts consistently",
    "Writing captions & hooks that convert",
    "Growing on Instagram, Facebook & TikTok",
    "Running affordable Meta & TikTok ads",
    "Reading your analytics & doubling down",
    "Turning followers into paying customers"
  ],
  "website-development": [
    "Planning a website that sells",
    "Choosing the right no-code platform",
    "Pages every business site needs",
    "Connecting a domain & business email",
    "Adding payment & WhatsApp buttons",
    "Making it fast & mobile-friendly",
    "Basic on-page SEO for your pages",
    "Publishing & maintaining it yourself"
  ],
  "seo-online-visibility": [
    "How Google search actually works",
    "Finding keywords your customers type",
    "Writing pages & articles that rank",
    "On-page SEO essentials",
    "Setting up Google Business Profile",
    "Getting found for local searches",
    "Free tools to track rankings & traffic",
    "A monthly SEO routine you can keep"
  ],
  "data-analysis-growth": [
    "The numbers every business should track",
    "Organising your data in spreadsheets",
    "Building simple, clear dashboards",
    "Reading sales, costs & profit with confidence",
    "Spotting trends & seasonality",
    "Using AI to summarise & explain data",
    "Turning insights into decisions",
    "Pricing & margin basics"
  ],
  "ecommerce-selling-online": [
    "Choosing the right platform for your products",
    "Setting up your online store",
    "Taking payments (cards & M-Pesa)",
    "Delivery, shipping & order tracking",
    "Photographing & listing products that sell",
    "Writing product descriptions that convert",
    "Launch promotions that drive first sales",
    "Handling orders & customer follow-up"
  ],
  "customer-service-excellence": [
    "The mindset of world-class service",
    "Turning complaints into loyalty",
    "Communication frameworks that calm any situation",
    "Phone, chat & in-person service standards",
    "Handling difficult customers with confidence",
    "Building a service culture for your team",
    "Scripts & standards your team keeps"
  ]
};
