<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sm="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
  <xsl:output method="html" encoding="UTF-8" indent="yes"/>
  <xsl:template match="/">
    <html lang="en">
      <head>
        <meta charset="UTF-8"/>
        <title>XML Sitemap</title>
        <style type="text/css">
          body { font-family: system-ui, sans-serif; margin: 2rem; color: #111; background: #fafafa; }
          h1 { font-size: 1.5rem; margin-bottom: 0.25rem; }
          p { color: #555; margin-top: 0; }
          table { border-collapse: collapse; width: 100%; max-width: 960px; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.06); }
          th, td { text-align: left; padding: 0.6rem 0.75rem; border-bottom: 1px solid #e5e7eb; font-size: 0.875rem; }
          th { background: #f3f4f6; font-weight: 600; }
          a { color: #4f46e5; text-decoration: none; }
          a:hover { text-decoration: underline; }
          .muted { color: #6b7280; }
        </style>
      </head>
      <body>
        <xsl:choose>
          <xsl:when test="sm:sitemapindex">
            <h1>Sitemap index</h1>
            <p>This index lists child sitemaps for this site.</p>
            <table>
              <thead>
                <tr><th>Sitemap</th><th>Last modified</th></tr>
              </thead>
              <tbody>
                <xsl:for-each select="sm:sitemapindex/sm:sitemap">
                  <tr>
                    <td><a href="{sm:loc}"><xsl:value-of select="sm:loc"/></a></td>
                    <td class="muted"><xsl:value-of select="sm:lastmod"/></td>
                  </tr>
                </xsl:for-each>
              </tbody>
            </table>
          </xsl:when>
          <xsl:otherwise>
            <h1>URL sitemap</h1>
            <p><xsl:value-of select="count(sm:urlset/sm:url)"/> URLs in this file.</p>
            <table>
              <thead>
                <tr><th>URL</th><th>Last modified</th><th>Images</th></tr>
              </thead>
              <tbody>
                <xsl:for-each select="sm:urlset/sm:url">
                  <tr>
                    <td><a href="{sm:loc}"><xsl:value-of select="sm:loc"/></a></td>
                    <td class="muted"><xsl:value-of select="sm:lastmod"/></td>
                    <td class="muted"><xsl:value-of select="count(image:image)"/></td>
                  </tr>
                </xsl:for-each>
              </tbody>
            </table>
          </xsl:otherwise>
        </xsl:choose>
      </body>
    </html>
  </xsl:template>
</xsl:stylesheet>
