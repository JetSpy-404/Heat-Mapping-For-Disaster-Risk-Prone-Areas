const https = require('https');
const http = require('http');
const fs = require('fs');
const path = require('path');
const { URL } = require('url');

// Inline JSDOM implementation to avoid dependencies
class SimpleDOMParser {
    parse(html) {
        const links = [];
        
        // Extract all <a href="..."> tags
        const linkRegex = /<a\s+[^>]*href=(["'])(.*?)\1[^>]*>/gi;
        let match;
        
        while ((match = linkRegex.exec(html)) !== null) {
            const fullTag = match[0];
            const href = match[2];
            
            // Extract link text if available
            const textMatch = fullTag.match(/<a[^>]*>([^<]*)<\/a>/i);
            const text = textMatch ? textMatch[1].toLowerCase() : '';
            
            links.push({ href, text });
        }
        
        return links;
    }
}

class RobustPagasaMonitor {
    constructor() {
        // Multiple data sources as fallbacks
        this.dataSources = [
            'https://www.pagasa.dost.gov.ph/tropical-cyclone/severe-weather-bulletin',
            'https://www.pagasa.dost.gov.ph/tropical-cyclone-advisory-iframe',
            'https://pubfiles.pagasa.dost.gov.ph/pagasaweb/tcb/'
        ];

        this.downloadFolder = './pagasa_advisories';
        this.checkInterval = 5 * 60 * 1000; // 5 minutes
        this.knownAdvisories = new Set();
        this.currentSourceIndex = 0;
        this.domParser = new SimpleDOMParser();
        this.newAdvisoriesFound = []; // Track new advisories for check-only mode

        // Multiple patterns to detect advisories
        this.ADVISORY_PATTERNS = [
            /advisory/i,
            /tropical.cyclone.bulletin/i,
            /tcb/i,
            /\.pdf$/i,
            /bulletin/i,
            /update/i
        ];

        this.init();
    }

    init() {
        // Create download directory
        if (!fs.existsSync(this.downloadFolder)) {
            fs.mkdirSync(this.downloadFolder, { recursive: true });
        }

        // Load previously seen advisories
        this.loadKnownAdvisories();

        console.log('🏁 PAGASA Advisory Monitor Started');
        console.log('📡 Data Sources:', this.dataSources.length);
        console.log('⏰ Check interval:', this.checkInterval / 60000, 'minutes');
        console.log('💾 Download folder:', path.resolve(this.downloadFolder));
        console.log('---');

        // Only start continuous monitoring if not in check-only mode
        if (!isCheckOnly) {
            // Test all sources first
            this.testAllSources().then(() => {
                // Start monitoring
                console.log('🚀 Starting continuous monitoring...');
                this.checkForAdvisories();
                setInterval(() => this.checkForAdvisories(), this.checkInterval);
            });
        }
    }

    async testAllSources() {
        console.log('🧪 Testing data sources...');
        
        for (let i = 0; i < this.dataSources.length; i++) {
            const source = this.dataSources[i];
            try {
                const html = await this.fetchPage(source);
                const links = this.findAdvisoryLinksRobust(html);
                console.log(`✓ Source ${i + 1}: ${source} - Found ${links.length} potential advisory links`);
            } catch (error) {
                console.log(`✗ Source ${i + 1}: ${source} - Failed: ${error.message}`);
            }
            await this.delay(1000); // Be respectful between tests
        }
    }

    loadKnownAdvisories() {
        try {
            const stateFile = path.join(this.downloadFolder, 'monitor_state.json');
            if (fs.existsSync(stateFile)) {
                const data = JSON.parse(fs.readFileSync(stateFile, 'utf8'));
                this.knownAdvisories = new Set(data.knownAdvisories || []);
                console.log(`📚 Loaded ${this.knownAdvisories.size} known advisories from previous session`);
            }
        } catch (error) {
            console.log('💾 No previous state found, starting fresh');
        }
    }

    saveKnownAdvisories() {
        try {
            const stateFile = path.join(this.downloadFolder, 'monitor_state.json');
            const data = {
                knownAdvisories: Array.from(this.knownAdvisories),
                lastUpdated: new Date().toISOString(),
                totalAdvisories: this.knownAdvisories.size,
                monitorVersion: '2.0.0'
            };
            fs.writeFileSync(stateFile, JSON.stringify(data, null, 2));
        } catch (error) {
            console.error('❌ Error saving state:', error.message);
        }
    }

    async checkForAdvisories() {
        const timestamp = new Date().toLocaleString();
        console.log(`\n🔍 [${timestamp}] Checking for new advisories...`);

        let success = false;

        // Try all sources until one works
        for (let i = 0; i < this.dataSources.length; i++) {
            const sourceIndex = (this.currentSourceIndex + i) % this.dataSources.length;
            const source = this.dataSources[sourceIndex];

            console.log(`   Trying source: ${source}`);

            try {
                const html = await this.fetchPage(source);
                const advisoryLinks = this.findAdvisoryLinksRobust(html);
                const newAdvisories = this.filterNewAdvisories(advisoryLinks);

                if (newAdvisories.length > 0) {
                    console.log(`🎉 Found ${newAdvisories.length} new advisory/ies!`);
                    this.newAdvisoriesFound = newAdvisories; // Store for check-only mode
                    await this.downloadNewAdvisories(newAdvisories);
                } else {
                    console.log('📭 No new advisories found');
                    this.newAdvisoriesFound = [];
                }

                success = true;
                this.currentSourceIndex = sourceIndex; // Remember working source
                break; // Exit loop if successful

            } catch (error) {
                console.log(`   ❌ Source failed: ${error.message}`);
                continue; // Try next source
            }
        }

        if (!success) {
            console.log('💥 All data sources failed. Will retry next interval.');
            this.newAdvisoriesFound = [];
        }
    }

    fetchPage(url) {
        return new Promise((resolve, reject) => {
            const urlObj = new URL(url);
            const protocol = urlObj.protocol === 'https:' ? https : http;
            
            const options = {
                timeout: 15000,
                headers: {
                    'User-Agent': 'Mozilla/5.0 (compatible; PAGASA-Monitor/2.0; Educational Use)',
                    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language': 'en-US,en;q=0.5'
                }
            };

            const req = protocol.get(url, options, (res) => {
                if (res.statusCode === 301 || res.statusCode === 302) {
                    // Handle redirects
                    const redirectUrl = new URL(res.headers.location, url).href;
                    console.log(`   ↪️ Redirected to: ${redirectUrl}`);
                    this.fetchPage(redirectUrl).then(resolve).catch(reject);
                    return;
                }

                if (res.statusCode !== 200) {
                    reject(new Error(`HTTP ${res.statusCode} - ${res.statusMessage}`));
                    return;
                }

                // Check content type
                const contentType = res.headers['content-type'] || '';
                if (!contentType.includes('text/html')) {
                    console.log(`   ⚠️ Unexpected content type: ${contentType}`);
                }

                let data = '';
                res.on('data', (chunk) => data += chunk);
                res.on('end', () => {
                    if (data.length === 0) {
                        reject(new Error('Empty response from server'));
                        return;
                    }
                    
                    if (data.length < 100) {
                        console.log(`   ⚠️ Very small response: ${data.length} bytes`);
                    }
                    
                    resolve(data);
                });
            });

            req.on('error', (error) => {
                reject(new Error(`Network error: ${error.message}`));
            });
            
            req.on('timeout', () => {
                req.destroy();
                reject(new Error('Request timeout after 15s'));
            });
        });
    }

    findAdvisoryLinksRobust(html) {
        const advisoryLinks = [];
        
        try {
            // Strategy 1: Use simple DOM parser
            const links = this.domParser.parse(html);
            
            links.forEach(link => {
                const href = link.href;
                const text = link.text;
                
                if (!href) return;
                
                // Check if this looks like an advisory link using multiple patterns
                const isAdvisory = this.ADVISORY_PATTERNS.some(pattern => 
                    pattern.test(href) || pattern.test(text)
                );
                
                if (isAdvisory) {
                    // Convert to absolute URL
                    const absoluteUrl = new URL(href, this.dataSources[this.currentSourceIndex]).href;
                    
                    // Prioritize PDFs but include other likely advisory links
                    if (href.includes('.pdf') || text.includes('advisory') || text.includes('bulletin')) {
                        if (!advisoryLinks.includes(absoluteUrl)) {
                            advisoryLinks.push(absoluteUrl);
                        }
                    }
                }
            });

            // Strategy 2: Direct PDF link search in page content
            const pdfLinks = html.match(/https?:\/\/[^"'\s<>]*\.pdf[^"'\s<>]*/gi) || [];
            pdfLinks.forEach(pdfLink => {
                if (this.ADVISORY_PATTERNS.some(pattern => pattern.test(pdfLink))) {
                    const cleanLink = pdfLink.replace(/["'<>]/g, '');
                    if (!advisoryLinks.includes(cleanLink)) {
                        advisoryLinks.push(cleanLink);
                    }
                }
            });

            // Strategy 3: Search for href attributes with advisory patterns
            const hrefRegex = /href=(["'])([^"']*?(advisory|tcb|bulletin)[^"']*?)\1/gi;
            let hrefMatch;
            while ((hrefMatch = hrefRegex.exec(html)) !== null) {
                const href = hrefMatch[2];
                const absoluteUrl = new URL(href, this.dataSources[this.currentSourceIndex]).href;
                if (!advisoryLinks.includes(absoluteUrl)) {
                    advisoryLinks.push(absoluteUrl);
                }
            }

        } catch (error) {
            console.error('   ⚠️ Error parsing HTML:', error.message);
            
            // Final fallback: simple regex search for common patterns
            const fallbackPatterns = [
                /href=["']([^"']*?advisory[^"']*?\.pdf)["']/gi,
                /href=["']([^"']*?tcb[^"']*?\.pdf)["']/gi,
                /href=["']([^"']*?bulletin[^"']*?\.pdf)["']/gi
            ];
            
            fallbackPatterns.forEach(pattern => {
                let match;
                while ((match = pattern.exec(html)) !== null) {
                    const href = match[1];
                    const absoluteUrl = new URL(href, this.dataSources[this.currentSourceIndex]).href;
                    if (!advisoryLinks.includes(absoluteUrl)) {
                        advisoryLinks.push(absoluteUrl);
                    }
                }
            });
        }

        // Remove duplicates and invalid links
        const uniqueLinks = [...new Set(advisoryLinks)].filter(link => 
            link && link.startsWith('http') && !link.includes('javascript:')
        );
        
        console.log(`   🔗 Found ${uniqueLinks.length} potential advisory links`);
        
        return uniqueLinks;
    }

    filterNewAdvisories(links) {
        return links.filter(link => {
            // Create a normalized version for comparison
            const normalizedLink = this.normalizeUrl(link);
            const isNew = !this.knownAdvisories.has(normalizedLink);
            
            if (isNew) {
                console.log(`   📋 New advisory detected: ${this.getFilenameFromUrl(link)}`);
                this.knownAdvisories.add(normalizedLink);
            }
            return isNew;
        });
    }

    normalizeUrl(url) {
        try {
            const urlObj = new URL(url);
            
            // Remove common tracking parameters
            const blacklistedParams = [
                'utm_source', 'utm_medium', 'utm_campaign', 
                'fbclid', 'gclid', 'utm_id', 'utm_term', 'utm_content'
            ];
            blacklistedParams.forEach(param => urlObj.searchParams.delete(param));
            
            return urlObj.href;
        } catch {
            return url; // Return original if URL parsing fails
        }
    }

    async downloadNewAdvisories(advisoryUrls) {
        let successCount = 0;
        
        console.log(`   📥 Starting download of ${advisoryUrls.length} new advisories...`);
        
        for (const url of advisoryUrls) {
            try {
                const success = await this.downloadFile(url);
                if (success) successCount++;
                
                // Respectful delay between downloads
                await this.delay(2000);
            } catch (error) {
                console.error(`   ❌ Failed to download ${this.getFilenameFromUrl(url)}:`, error.message);
            }
        }
        
        this.saveKnownAdvisories();
        console.log(`   ✅ Successfully downloaded ${successCount}/${advisoryUrls.length} new advisories`);
    }

    downloadFile(url) {
        return new Promise((resolve, reject) => {
            const filename = this.generateFilename(url);
            const filepath = path.join(this.downloadFolder, filename);
            
            // Check if file already exists (in case of duplicate detection)
            if (fs.existsSync(filepath)) {
                console.log(`   ⚠️ File already exists: ${filename}`);
                resolve(false);
                return;
            }
            
            console.log(`   📥 Downloading: ${filename}`);
            
            const urlObj = new URL(url);
            const protocol = urlObj.protocol === 'https:' ? https : http;
            
            const file = fs.createWriteStream(filepath);
            const req = protocol.get(url, {
                timeout: 20000,
                headers: {
                    'User-Agent': 'Mozilla/5.0 (compatible; PAGASA-Monitor/2.0; Educational Use)'
                }
            }, (res) => {
                if (res.statusCode !== 200) {
                    reject(new Error(`HTTP ${res.statusCode} - ${res.statusMessage}`));
                    return;
                }

                // Check if it's actually a PDF
                const contentType = res.headers['content-type'];
                if (contentType && !contentType.includes('pdf') && !contentType.includes('octet-stream')) {
                    reject(new Error(`Unexpected content type: ${contentType}`));
                    return;
                }

                const fileSize = parseInt(res.headers['content-length']) || 0;
                let downloadedSize = 0;
                
                res.on('data', (chunk) => {
                    downloadedSize += chunk.length;
                });
                
                res.pipe(file);
                
                file.on('finish', () => {
                    file.close();
                    const sizeMB = (downloadedSize / (1024 * 1024)).toFixed(2);
                    console.log(`   ✅ Successfully downloaded: ${filename} (${sizeMB} MB)`);
                    resolve(true);
                });
            });

            req.on('error', (err) => {
                // Clean up partial file
                try { 
                    if (fs.existsSync(filepath)) {
                        fs.unlinkSync(filepath); 
                        console.log(`   🗑️ Deleted partial file: ${filename}`);
                    }
                } catch (e) {}
                reject(new Error(`Download failed: ${err.message}`));
            });

            req.on('timeout', () => {
                req.destroy();
                try { 
                    if (fs.existsSync(filepath)) {
                        fs.unlinkSync(filepath); 
                    }
                } catch (e) {}
                reject(new Error('Download timeout after 20s'));
            });
            
            file.on('error', (err) => {
                reject(new Error(`File write error: ${err.message}`));
            });
        });
    }

    generateFilename(url) {
        try {
            const urlObj = new URL(url);
            let filename = path.basename(urlObj.pathname);
            
            if (!filename || filename === '/') {
                // Create descriptive filename with timestamp
                const now = new Date();
                const dateStr = now.toISOString().split('T')[0]; // YYYY-MM-DD
                const timeStr = now.toTimeString().split(' ')[0].replace(/:/g, '-'); // HH-MM-SS
                filename = `advisory_${dateStr}_${timeStr}.pdf`;
            }
            
            // Clean filename and ensure .pdf extension
            filename = filename.replace(/[^a-zA-Z0-9._-]/g, '_');
            if (!filename.toLowerCase().endsWith('.pdf')) {
                filename += '.pdf';
            }
            
            return filename;
        } catch (error) {
            return `advisory_${Date.now()}.pdf`;
        }
    }

    getFilenameFromUrl(url) {
        try {
            const filename = path.basename(new URL(url).pathname);
            return filename || 'advisory.pdf';
        } catch {
            return 'advisory.pdf';
        }
    }

    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

// Check if running with --check-only flag
const isCheckOnly = process.argv.includes('--check-only');

// Utility function to check if we're running directly
function main() {
    console.log('=========================================');
    console.log('🌪️  PAGASA Advisory Monitor v2.0');
    console.log('📧 Educational Use - Be Respectful to Servers');
    console.log('=========================================\n');

    // Create monitor instance
    const monitor = new RobustPagasaMonitor();

    if (isCheckOnly) {
        // Run single check and output JSON result
        monitor.checkForAdvisories().then(() => {
            // Output JSON result for PHP to parse
            const result = {
                new_advisories: monitor.newAdvisoriesFound || [],
                checked_at: new Date().toISOString()
            };
            console.log(JSON.stringify(result));
            process.exit(0);
        }).catch((error) => {
            console.error('Check failed:', error);
            console.log(JSON.stringify({ error: error.message, new_advisories: [] }));
            process.exit(1);
        });
        return;
    }

    // Graceful shutdown handling
    process.on('SIGINT', () => {
        console.log('\n🛑 Received shutdown signal...');
        console.log('💾 Saving monitor state...');
        monitor.saveKnownAdvisories();
        console.log('👋 Monitor stopped gracefully. Goodbye!');
        process.exit(0);
    });

    process.on('SIGTERM', () => {
        console.log('\n🛑 Monitor is being terminated...');
        monitor.saveKnownAdvisories();
        process.exit(0);
    });

    process.on('uncaughtException', (error) => {
        console.error('\n💥 Critical error:', error);
        console.log('💾 Attempting to save state before exit...');
        monitor.saveKnownAdvisories();
        process.exit(1);
    });
}

// Start if this file is run directly
if (require.main === module) {
    main();
}

// Export for testing or module use
module.exports = RobustPagasaMonitor;