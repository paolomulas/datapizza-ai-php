# 🛠️ Sysadmin Agent Example

This example demonstrates how to use **Datapizza-AI-PHP** to build the "Sysadmin Agent" featured in the ADMIN Magazine article.

---

## 📋 What it does

The agent provides comprehensive system monitoring capabilities:

- **✅ Disk Space Monitoring** - Check disk usage via `DiskSpaceTool`
- **✅ System Uptime & Load** - Monitor uptime and system load via `SystemUptimeTool`
- **✅ Log Analysis** - Search log files for patterns (safely, with whitelisted paths) via `LogGrepTool`
- **✅ High-Level Queries** - Answer complex questions like "Is the server healthy?" by combining multiple tools

All operations are orchestrated by a ReAct-style agent that intelligently decides which tools to call, in which order, and explains results in natural language.

---

## 🚀 How to run

From the repository root:

```bash
cd examples/05_sysadmin
php sysadmin_agent.php
```

### Demo Runs

The script will execute several demonstrations:

1. Basic system health check
2. Root filesystem disk space analysis
3. Log analysis on `/var/log/syslog` (if available)
4. Full server health report combining all tools

---

## 📦 Requirements

### System Requirements
- **PHP 7.4+** (CLI)
- Network access to your LLM provider (e.g., OpenAI)
- Read access to `/proc/uptime` and related `/proc` files
- **Optional:** Read access to `/var/log/syslog` for log analysis

### Environment Variables

Required variables (loaded from `.env` in the repository root):

- `OPENAI_API_KEY` (or your provider's key)
- Any additional keys required by your Datapizza-AI-PHP setup

---

## 🔒 Security

All tools use **safe, read-only operations**:

- ✅ No arbitrary shell commands
- ✅ Whitelisted log paths only
- ✅ Native PHP functions for system checks

This design makes the example suitable for production-like environments, such as running from cron for daily health checks.

---

## 🔧 Extending this example

The Sysadmin Agent is designed as a starting point. You can easily add new capabilities by creating additional tools.

### Ideas for New Tools

#### 1. **ServiceStatusTool**
Check whether critical services (e.g., nginx, postgresql, sshd) are running.

**Example query:** *"Is nginx running and responding on port 80?"*

#### 2. **MemoryUsageTool**
Report RAM usage and swap pressure.

**Example response:** *"The server is healthy, but RAM usage is above 80%."*

#### 3. **DatabaseHealthTool**
Run lightweight diagnostic queries against MySQL/PostgreSQL (e.g., `SELECT 1`) and scan logs for recent database errors.

### How to Add a New Tool

1. **Create the tool class:**
   ```php
   // datapizza/tools/MyNewTool.php
   class MyNewTool extends BaseTool {
       public function execute($params) {
           // Your implementation
       }
       
       public function get_parameters_schema() {
           // Return JSON schema
       }
   }
   ```

2. **Register it in the agent:**
   ```php
   // examples/05_sysadmin/sysadmin_agent.php
   $tools = [
       new DiskSpaceTool(),
       new SystemUptimeTool(),
       new LogGrepTool(),
       new MyNewTool(), // Your custom tool
   ];
   ```

3. **Run the agent** - It will automatically discover and use your tool when appropriate.

---

## 📰 Created for ADMIN Magazine

This example was created as companion code for the ADMIN Magazine article:

**"Datapizza-AI-PHP: Edge AI Automation on a 2011 Raspberry Pi"**  
*ADMIN Magazine, Issue 92 – February 2026*

---

