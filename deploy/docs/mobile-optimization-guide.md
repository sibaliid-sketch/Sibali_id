# Panduan Optimisasi Mobile & PWA

## Critical Rendering Path Optimization

### Above-the-Fold Content
- [ ] Identify critical content for mobile viewport
- [ ] Prioritize loading of hero section, navigation, and primary CTA
- [ ] Implement lazy loading for below-the-fold content
- [ ] Optimize font loading (FOUT/FOIT prevention)

### Resource Prioritization
```html
<!-- Preload critical resources -->
<link rel="preload" href="/css/critical.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link rel="preload" href="/js/critical.js" as="script">

<!-- Preconnect to external domains -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<!-- DNS prefetch for non-critical domains -->
<link rel="dns-prefetch" href="//www.google-analytics.com">
<link rel="dns-prefetch" href="//www.googletagmanager.com">
```

### Critical CSS Extraction
```css
/* Critical CSS for mobile first */
.hero-section {
  background: linear-gradient(135deg, #8B0000, #00008B);
  min-height: 100vh;
  display: flex;
  align-items: center;
}

.hero-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
  text-align: center;
  color: #FFFFFF;
}

.hero-title {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 1rem;
  line-height: 1.2;
}

.hero-subtitle {
  font-size: 1.2rem;
  margin-bottom: 2rem;
  opacity: 0.9;
}

.cta-button {
  background: #87CEEB;
  color: #00008B;
  padding: 1rem 2rem;
  border-radius: 50px;
  text-decoration: none;
  font-weight: 600;
  display: inline-block;
  transition: all 0.3s ease;
}
```

## Image Optimization Strategy

### Responsive Image Breakpoints
```html
<!-- Responsive images with multiple sizes -->
<picture>
  <source media="(max-width: 480px)" srcset="hero-mobile-480w.webp 480w, hero-mobile-960w.webp 960w">
  <source media="(max-width: 768px)" srcset="hero-tablet-768w.webp 768w, hero-tablet-1536w.webp 1536w">
  <img src="hero-desktop-1200w.webp"
       alt="Sibali.id Learning Platform"
       loading="eager"
       decoding="async"
       width="1200"
       height="600">
</picture>
```

### Image Format Optimization
```javascript
// WebP with fallback support
function supportsWebP() {
  const canvas = document.createElement('canvas');
  canvas.width = 1;
  canvas.height = 1;
  return canvas.toDataURL('image/webp').indexOf('webp') > -1;
}

// Dynamic image loading
const imageLoader = (src, fallbackSrc) => {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => resolve(src);
    img.onerror = () => resolve(fallbackSrc);
    img.src = src;
  });
};
```

### Image Compression Pipeline
```javascript
// Image optimization configuration
const sharp = require('sharp');

const optimizeImage = async (inputPath, outputPath, options = {}) => {
  const {
    width = 1200,
    height = 600,
    quality = 80,
    format = 'webp'
  } = options;

  await sharp(inputPath)
    .resize(width, height, {
      fit: 'cover',
      position: 'center'
    })
    .webp({ quality })
    .toFile(outputPath);
};
```

## Lazy Loading Implementation

### Native Lazy Loading
```html
<!-- Native lazy loading for images -->
<img src="placeholder.jpg"
     data-src="actual-image.jpg"
     alt="Lazy loaded image"
     loading="lazy"
     decoding="async">

<!-- For background images -->
<div class="lazy-bg"
     data-bg="background-image.jpg"
     style="min-height: 300px;">
</div>
```

### Intersection Observer Implementation
```javascript
// Advanced lazy loading with Intersection Observer
class LazyLoader {
  constructor(options = {}) {
    this.options = {
      rootMargin: '50px 0px',
      threshold: 0.01,
      ...options
    };
    this.observer = null;
    this.init();
  }

  init() {
    if ('IntersectionObserver' in window) {
      this.observer = new IntersectionObserver(
        this.handleIntersection.bind(this),
        this.options
      );
    }
  }

  observe(element) {
    if (this.observer) {
      this.observer.observe(element);
    } else {
      // Fallback for older browsers
      this.loadElement(element);
    }
  }

  handleIntersection(entries) {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        this.loadElement(entry.target);
        this.observer.unobserve(entry.target);
      }
    });
  }

  loadElement(element) {
    const src = element.dataset.src;
    const bg = element.dataset.bg;

    if (src) {
      element.src = src;
      element.classList.add('loaded');
    }

    if (bg) {
      element.style.backgroundImage = `url(${bg})`;
      element.classList.add('loaded');
    }
  }
}

// Usage
const lazyLoader = new LazyLoader();
document.querySelectorAll('.lazy').forEach(img => {
  lazyLoader.observe(img);
});
```

## Service Worker Lifecycle

### Service Worker Registration
```javascript
// service-worker.js registration
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js', {
      scope: '/'
    })
    .then(registration => {
      console.log('SW registered:', registration.scope);

      // Handle updates
      registration.addEventListener('updatefound', () => {
        const newWorker = registration.installing;
        newWorker.addEventListener('statechange', () => {
          if (newWorker.state === 'installed') {
            if (navigator.serviceWorker.controller) {
              // New version available
              showUpdateNotification();
            }
          }
        });
      });
    })
    .catch(error => {
      console.log('SW registration failed:', error);
    });
  });
}
```

### Service Worker Implementation
```javascript
// sw.js - Service Worker for caching and offline support
const CACHE_NAME = 'sibali-v1.0.0';
const STATIC_CACHE = 'sibali-static-v1.0.0';
const DYNAMIC_CACHE = 'sibali-dynamic-v1.0.0';

// Resources to cache immediately
const STATIC_ASSETS = [
  '/',
  '/css/app.css',
  '/js/app.js',
  '/images/logo.webp',
  '/offline.html'
];

// Install event - cache static assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => cache.addAll(STATIC_ASSETS))
  );
  self.skipWaiting();
});

// Activate event - clean old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch event - serve from cache or network
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip cross-origin requests
  if (url.origin !== location.origin) return;

  // Handle API requests
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(networkFirst(request));
    return;
  }

  // Handle static assets
  if (request.destination === 'style' ||
      request.destination === 'script' ||
      request.destination === 'image') {
    event.respondWith(cacheFirst(request));
    return;
  }

  // Default: network first for HTML
  event.respondWith(networkFirst(request));
});

// Cache-first strategy for static assets
async function cacheFirst(request) {
  try {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }

    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      const cache = await caches.open(STATIC_CACHE);
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    return caches.match('/offline.html');
  }
}

// Network-first strategy for dynamic content
async function networkFirst(request) {
  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      const cache = await caches.open(DYNAMIC_CACHE);
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    return caches.match('/offline.html');
  }
}
```

## Manifest Tuning

### Web App Manifest
```json
{
  "name": "Sibali.id - Platform Pembelajaran Bahasa Inggris",
  "short_name": "Sibali.id",
  "description": "Platform pembelajaran bahasa Inggris terintegrasi dengan LMS, CRM, dan tools internal",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#FFFFFF",
  "theme_color": "#8B0000",
  "orientation": "portrait-primary",
  "scope": "/",
  "lang": "id-ID",
  "categories": ["education", "productivity"],
  "icons": [
    {
      "src": "/icons/icon-72x72.webp",
      "sizes": "72x72",
      "type": "image/webp",
      "purpose": "any maskable"
    },
    {
      "src": "/icons/icon-96x96.webp",
      "sizes": "96x96",
      "type": "image/webp",
      "purpose": "any maskable"
    },
    {
      "src": "/icons/icon-128x128.webp",
      "sizes": "128x128",
      "type": "image/webp",
      "purpose": "any maskable"
    },
    {
      "src": "/icons/icon-144x144.webp",
      "sizes": "144x144",
      "type": "image/webp",
      "purpose": "any maskable"
    },
    {
      "src": "/icons/icon-152x152.webp",
      "sizes": "152x152",
      "type": "image/webp",
      "purpose": "any maskable"
    },
    {
      "src": "/icons/icon-192x192.webp",
      "sizes": "192x192",
      "type": "image/webp",
      "purpose": "any maskable"
    },
    {
      "src": "/icons/icon-384x384.webp",
      "sizes": "384x384",
      "type": "image/webp",
      "purpose": "any maskable"
    },
    {
      "src": "/icons/icon-512x512.webp",
      "sizes": "512x512",
      "type": "image/webp",
      "purpose": "any maskable"
    }
  ],
  "shortcuts": [
    {
      "name": "Dashboard",
      "short_name": "Dashboard",
      "description": "Akses dashboard utama",
      "url": "/dashboard",
      "icons": [{ "src": "/icons/dashboard.webp", "sizes": "96x96" }]
    },
    {
      "name": "Kelas Saya",
      "short_name": "Kelas",
      "description": "Lihat kelas yang diikuti",
      "url": "/my-classes",
      "icons": [{ "src": "/icons/classes.webp", "sizes": "96x96" }]
    }
  ],
  "screenshots": [
    {
      "src": "/screenshots/mobile-dashboard.webp",
      "sizes": "390x844",
      "type": "image/webp",
      "label": "Dashboard mobile Sibali.id"
    },
    {
      "src": "/screenshots/desktop-lms.webp",
      "sizes": "1280x720",
      "type": "image/webp",
      "label": "Platform LMS Sibali.id"
    }
  ]
}
```

### Offline Fallbacks

### Offline Page Implementation
```html
<!-- offline.html -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sibali.id - Offline</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #8B0000, #00008B);
            color: white;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .offline-container {
            text-align: center;
            max-width: 400px;
        }
        .offline-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .offline-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .offline-message {
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        .retry-button {
            background: #87CEEB;
            color: #00008B;
            padding: 1rem 2rem;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .retry-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-icon">📱</div>
        <h1 class="offline-title">Koneksi Terputus</h1>
        <p class="offline-message">
            Sepertinya Anda sedang offline. Silakan periksa koneksi internet Anda dan coba lagi.
        </p>
        <button class="retry-button" onclick="window.location.reload()">
            Coba Lagi
        </button>
    </div>

    <script>
        // Auto-retry when connection is restored
        window.addEventListener('online', () => {
            window.location.reload();
        });
    </script>
</body>
</html>
```

### Offline Data Synchronization
```javascript
// Offline data sync queue
class OfflineSync {
  constructor() {
    this.queue = [];
    this.isOnline = navigator.onLine;
    this.init();
  }

  init() {
    window.addEventListener('online', () => {
      this.isOnline = true;
      this.processQueue();
    });

    window.addEventListener('offline', () => {
      this.isOnline = false;
    });
  }

  addToQueue(action) {
    this.queue.push({
      ...action,
      timestamp: Date.now(),
      id: Math.random().toString(36).substr(2, 9)
    });

    if (this.isOnline) {
      this.processQueue();
    } else {
      // Store in IndexedDB for persistence
      this.storeOffline();
    }
  }

  async processQueue() {
    if (!this.isOnline || this.queue.length === 0) return;

    const actions = [...this.queue];
    this.queue = [];

    for (const action of actions) {
      try {
        await this.executeAction(action);
      } catch (error) {
        console.error('Sync failed:', error);
        // Re-queue failed actions
        this.queue.unshift(action);
        break;
      }
    }
  }

  async executeAction(action) {
    // Implement action execution logic
    switch (action.type) {
      case 'ENROLL_COURSE':
        return fetch('/api/enroll', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(action.payload)
        });
      // Add other action types
    }
  }

  storeOffline() {
    // Store queue in IndexedDB
    const request = indexedDB.open('sibali-offline', 1);

    request.onupgradeneeded = event => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains('sync-queue')) {
        db.createObjectStore('sync-queue', { keyPath: 'id' });
      }
    };

    request.onsuccess = event => {
      const db = event.target.result;
      const transaction = db.transaction(['sync-queue'], 'readwrite');
      const store = transaction.objectStore('sync-queue');

      this.queue.forEach(action => {
        store.put(action);
      });
    };
  }
}

// Usage
const offlineSync = new OfflineSync();

// Add actions to sync queue
document.getElementById('enroll-btn').addEventListener('click', () => {
  offlineSync.addToQueue({
    type: 'ENROLL_COURSE',
    payload: { courseId: 123, userId: 456 }
  });
});
```

## Performance Budgets

### Core Web Vitals Targets
```javascript
// Performance budget configuration
const performanceBudget = {
  // Core Web Vitals
  lcp: {
    target: 2500, // ms
    warning: 4000
  },
  fid: {
    target: 100, // ms
    warning: 300
  },
  cls: {
    target: 0.1,
    warning: 0.25
  },

  // Additional metrics
  fcp: {
    target: 1800, // ms
    warning: 3000
  },
  ttfb: {
    target: 800, // ms
    warning: 1800
  },

  // Bundle sizes
  js: {
    target: 200, // KB
    warning: 300
  },
  css: {
    target: 50, // KB
    warning: 100
  },

  // Image optimization
  images: {
    maxSize: 500, // KB per image
    formats: ['webp', 'avif']
  }
};

// Performance monitoring
class PerformanceMonitor {
  constructor(budget) {
    this.budget = budget;
    this.init();
  }

  init() {
    // Monitor Core Web Vitals
    this.monitorWebVitals();

    // Monitor bundle sizes
    this.monitorBundleSizes();

    // Monitor images
    this.monitorImages();
  }

  monitorWebVitals() {
    // Largest Contentful Paint
    new PerformanceObserver(entry => {
      const lcp = entry.getEntries()[0].startTime;
      this.checkMetric('lcp', lcp);
    }).observe({ entryTypes: ['largest-contentful-paint'] });

    // First Input Delay
    new PerformanceObserver(entry => {
      const fid = entry.getEntries()[0].processingStart - entry.getEntries()[0].startTime;
      this.checkMetric('fid', fid);
    }).observe({ entryTypes: ['first-input'] });

    // Cumulative Layout Shift
    new PerformanceObserver(entry => {
      let clsValue = 0;
      for (const entry of entry.getEntries()) {
        if (!entry.hadRecentInput) {
          clsValue += entry.value;
        }
      }
      this.checkMetric('cls', clsValue);
    }).observe({ entryTypes: ['layout-shift'] });
  }

  checkMetric(metric, value) {
    const config = this.budget[metric];
    if (!config) return;

    if (value > config.warning) {
      console.error(`🚨 ${metric.toUpperCase()} budget exceeded: ${value} (warning: ${config.warning})`);
    } else if (value > config.target) {
      console.warn(`⚠️ ${metric.toUpperCase()} approaching budget: ${value} (target: ${config.target})`);
    } else {
      console.log(`✅ ${metric.toUpperCase()} within budget: ${value}`);
    }
  }

  monitorBundleSizes() {
    // Monitor JavaScript bundle size
    if (performance.getEntriesByType) {
      window.addEventListener('load', () => {
        const resources = performance.getEntriesByType('resource');
        const jsResources = resources.filter(r => r.name.endsWith('.js'));

        jsResources.forEach(resource => {
          const sizeKB = resource.transferSize / 1024;
          this.checkMetric('js', sizeKB);
        });
      });
    }
  }

  monitorImages() {
    const images = document.querySelectorAll('img');
    images.forEach(img => {
      img.addEventListener('load', () => {
        // Check if image is WebP/AVIF
        const supported = this.budget.images.formats.some(format =>
          img.currentSrc.includes(format)
        );

        if (!supported) {
          console.warn(`⚠️ Image not optimized: ${img.src}`);
        }
      });
    });
  }
}

// Initialize performance monitoring
const perfMonitor = new PerformanceMonitor(performanceBudget);
```

### Lighthouse Audit Baseline

#### Performance Score Targets
- **Overall Performance**: > 90
- **Accessibility**: > 95
- **Best Practices**: > 95
- **SEO**: > 90
- **PWA**: > 90

#### Lighthouse Configuration
```javascript
// lighthouse-config.json
{
  "extends": "lighthouse:default",
  "settings": {
    "formFactor": "mobile",
    "screenEmulation": {
      "mobile": true,
      "width": 360,
      "height": 640,
      "deviceScaleFactor": 2.625,
      "disabled": false
    },
    "emulatedUserAgent": "Mozilla/5.0 (Linux; Android 7.0; Moto G (4)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4590.2 Mobile Safari/537.36 Chrome-Lighthouse",
    "throttling": {
      "rttMs": 150,
      "throughputKbps": 1638.4,
      "cpuSlowdownMultiplier": 4
    }
  },
  "passes": [
    {
      "passName": "defaultPass",
      "recordTrace": true,
      "useThrottling": true,
      "pauseAfterFcpMs": 1000,
      "pauseAfterLoadMs": 1000,
      "networkQuietThresholdMs": 1000,
      "cpuQuietThresholdMs": 1000
    }
  ],
  "audits": [
    "largest-contentful-paint",
    "cumulative-layout-shift",
    "first-contentful-paint",
    "speed-index",
    "interactive",
    "total-blocking-time"
  ]
}
```

### Acceptance Criteria

#### Mobile Performance Requirements
- [ ] LCP < 2.5 seconds on 3G
- [ ] FID < 100ms
- [ ] CLS < 0.1
- [ ] Total bundle size < 200KB gzipped
- [ ] Time to interactive < 3 seconds
- [ ] Lighthouse Performance score > 90

#### PWA Requirements
- [ ] App installable from browser
- [ ] Works offline with core functionality
- [ ] Push notifications supported
- [ ] Background sync implemented
- [ ] Web app manifest properly configured

#### Mobile UX Requirements
- [ ] Touch targets minimum 44px
- [ ] Content readable without zooming
- [ ] Forms usable on mobile
- [ ] Navigation accessible with thumb
- [ ] Content loads progressively

## Implementation Checklist

### Critical Path Optimization
- [ ] Server-side rendering for initial page load
- [ ] Critical CSS inlined in HTML head
- [ ] Above-the-fold images optimized and preloaded
- [ ] JavaScript execution deferred for non-critical scripts
- [ ] Web fonts optimized with font-display: swap

### Image Optimization
- [ ] All images converted to WebP/AVIF with fallbacks
- [ ] Responsive images implemented with srcset
- [ ] Image lazy loading for below-the-fold content
- [ ] Image CDN with automatic optimization
- [ ] Proper alt texts for accessibility

### Caching Strategy
- [ ] Service worker implemented for offline support
- [ ] Cache-first strategy for static assets
- [ ] Network-first strategy for dynamic content
- [ ] Background sync for offline actions
- [ ] Cache versioning and cleanup

### Performance Monitoring
- [ ] Core Web Vitals tracking implemented
- [ ] Performance budgets defined and monitored
- [ ] Lighthouse CI integrated in deployment
- [ ] Real user monitoring (RUM) configured
- [ ] Performance alerts set up

### Progressive Enhancement
- [ ] Core functionality works without JavaScript
- [ ] Graceful degradation for older browsers
- [ ] PWA features enhance but don't break basic experience
- [ ] Offline functionality degrades gracefully
- [ ] Network-dependent features handle failures

## Tools and Resources

### Development Tools
- **Lighthouse**: Performance auditing
- **WebPageTest**: Real-world performance testing
- **Chrome DevTools**: Performance profiling
- **Webpack Bundle Analyzer**: Bundle size analysis
- **ImageOptim**: Image optimization

### Monitoring Tools
- **Google Analytics**: Real user performance data
- **Sentry**: Error tracking and performance monitoring
- **New Relic**: Application performance monitoring
- **Pingdom**: Uptime and performance monitoring
- **GTmetrix**: Performance analysis

### CDN and Optimization Services
- **Cloudflare**: CDN with image optimization
- **Imgix**: Real-time image processing
- **TinyPNG**: Image compression
- **WebP Converter**: Format conversion
- **Critical CSS Generator**: Automated critical CSS extraction
