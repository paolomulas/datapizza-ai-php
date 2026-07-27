# 🛠️ Sysadmin Agent Example

This example demonstrates three narrow, read-only inspection tools used by the "Sysadmin Agent" featured in the ADMIN Magazine article and Chapter 8 of the book.

> The local tool boundaries are covered by an offline test. The full ReAct example additionally requires provider credentials and an operator-selected, non-sensitive log through `SYSADMIN_DEMO_LOG`.

---

## 📋 What it does

The agent provides bounded inspection capabilities:

- **✅ Disk Space Monitoring** - Check disk usage via `DiskSpaceTool`
- **✅ System Uptime** - Read one proc-style uptime source via `SystemUptimeTool`
- **✅ Log Analysis** - Search an approved `.log` or `.txt` file for literal text via `LogGrepTool`
- **✅ High-Level Queries** - Answer complex questions like "Is the server healthy?" by combining multiple tools

The tools expose no general shell or caller-controlled command string. Their observations remain bounded evidence, not a complete health verdict.

---

## 🚀 How to run

From the repository root:

```bash
php examples/04_advanced/tests/test_sysadmin_tools.php
```

For a reviewed live demo, set `SYSADMIN_DEMO_LOG` to an approved readable file, configure the provider key, and run `php examples/05_sysadmin/sysadmin_agent.php`.

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

