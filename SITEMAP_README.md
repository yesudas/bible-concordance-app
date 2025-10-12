# Sitemap Generator for Bible Concordance

This directory contains a comprehensive sitemap generation system for the Bible Concordance application.

## Files

### 1. `sitemap.php`
The main sitemap generator script that creates XML sitemaps for all content in the application.

**Features:**
- Recursively processes all languages, bibles, letters, words, and verses
- Creates multiple sitemap files with max 10,000 URLs each
- Generates a master sitemap.xml that indexes all individual sitemaps
- Memory-efficient processing for large datasets
- Error handling and progress reporting
- Automatic cleanup of old sitemap files

**Usage:**
```bash
php sitemap.php
```

### 2. `generate_sitemap.sh`
A shell script wrapper for automated sitemap generation via cron jobs.

**Features:**
- Logging with timestamps
- Error checking
- Can be scheduled via cron
- Optional Google Search Console ping

**Usage:**
```bash
./generate_sitemap.sh
```

**Cron Setup Example:**
```bash
# Generate sitemap daily at 2 AM
0 2 * * * /path/to/bible-concordance/generate_sitemap.sh
```

## Generated Files

### Master Sitemap
- `sitemap.xml` - Main sitemap index that references all individual sitemaps

### Individual Sitemaps
- `sitemap-1.xml`, `sitemap-2.xml`, etc. - Individual sitemap files with max 10,000 URLs each

## URL Structure

The sitemap includes the following URL patterns:

1. **Home Page**
   - `https://www.wordofgod.in/bible-concordance/`
   - Priority: 1.0, Change Frequency: daily

2. **Language Pages**
   - `https://www.wordofgod.in/bible-concordance/?lang=English`
   - Priority: 0.9, Change Frequency: weekly

3. **Bible Pages**
   - `https://www.wordofgod.in/bible-concordance/?lang=English&bible=KJV1769`
   - Priority: 0.8, Change Frequency: weekly

4. **Letter Pages**
   - `https://www.wordofgod.in/bible-concordance/?lang=English&bible=KJV1769&letter=A`
   - Priority: 0.7, Change Frequency: monthly

5. **Word Pages (Verses View)**
   - `https://www.wordofgod.in/bible-concordance/?lang=English&bible=KJV1769&letter=A&word=Abraham`
   - Priority: 0.6, Change Frequency: monthly

## Configuration

Key settings in `sitemap.php`:

```php
$baseUrl = 'https://www.wordofgod.in/bible-concordance/';
$maxUrlsPerSitemap = 10000;
$currentDate = date('Y-m-d');
```

## Performance

- **Memory Limit**: 1GB (configurable)
- **Execution Time**: 10 minutes max (configurable)
- **Memory Efficient**: Uses chunked processing to handle large datasets
- **Progress Reporting**: Real-time progress updates during generation

## Search Engine Submission

After generation, submit the master sitemap to search engines:

- **Google Search Console**: https://search.google.com/search-console
- **Bing Webmaster Tools**: https://www.bing.com/webmasters
- **Submit URL**: `https://www.wordofgod.in/bible-concordance/sitemap.xml`

## Logging

The shell script generates logs in `sitemap_generation.log` with timestamps for:
- Generation start/completion times
- Success/failure status
- Any errors encountered

## Troubleshooting

### Common Issues

1. **Memory Exhaustion**
   - Increase memory_limit in sitemap.php
   - Check available server memory

2. **Execution Timeout**
   - Increase max_execution_time in sitemap.php
   - Consider running via CLI instead of web browser

3. **File Permissions**
   - Ensure write permissions in the directory
   - Check that PHP can create/write XML files

4. **Missing Data**
   - Verify data directory structure
   - Check JSON file integrity
   - Ensure concordance files exist

### Debug Mode

For debugging, you can modify the script to output more verbose information or reduce the dataset for testing.

## Maintenance

- **Schedule**: Run daily or weekly via cron
- **Monitoring**: Check log files for errors
- **Cleanup**: Old sitemap files are automatically removed on each run
- **Updates**: Regenerate when new content is added to the concordance

## Example Output

```
Starting sitemap generation...
Base URL: https://www.wordofgod.in/bible-concordance/
Date: 2025-10-12

Added home page URL
Found 2 languages
Processing language 'English' with 8 bibles
  Bible 'KJV1769' has 25 letters
    Letter 'A' has 2451 words
...

Created sitemap-1.xml with 10000 URLs
Created sitemap-2.xml with 8456 URLs
Created master sitemap.xml

============================================================
SITEMAP GENERATION COMPLETED!
============================================================
Execution time: 45.23 seconds
Peak memory usage: 156.7 MB
Total URLs generated: 18,456
Sitemap files created: 2
```

This sitemap system ensures comprehensive search engine indexing of all Bible concordance content across multiple languages and translations.