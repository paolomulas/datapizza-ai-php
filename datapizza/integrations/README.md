# Integrations

PHP-specific integrations for external systems.

## n8n/ - Workflow Automation

Webhook endpoint for n8n workflows.

**Usage:**
POST /datapizza/integrations/n8n/endpoint.php
{
"query": "What is 15 * 23?",
"tools": ["calculator"],
"llm_provider": "deepseek"
}

text

## mcp/ - Model Context Protocol

MCP server for Claude Desktop, Cursor, Zed.

**Usage:**
php datapizza/integrations/mcp/server.php

text
