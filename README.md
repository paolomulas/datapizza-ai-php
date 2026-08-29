# 🍕 Datapizza-AI PHP  
> *Designed and built on a Raspberry Pi Model B (2011): readable code, modest hardware, and no unnecessary layers.*

[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.x-blue.svg)]()
[![Platform](https://img.shields.io/badge/Runs_on-RaspberryPi_1_Model_B-red.svg)]()
[![Architecture](https://img.shields.io/badge/Architecture-API--first-lightgrey.svg)]()
[![Focus](https://img.shields.io/badge/Focus-Educational-yellow.svg)]()
[![Power](https://img.shields.io/badge/Power_Usage-~3W-orange.svg)]()
[![Status](https://img.shields.io/badge/Status-Active-brightgreen.svg)]()
[![Featured in ADMIN Magazine](https://img.shields.io/badge/Featured_in-ADMIN_Magazine_Feb_2026-blue.svg)]()

**Datapizza-AI PHP** is an educational, ultra-minimal port of the original [Datapizza AI](https://github.com/datapizza-labs/datapizza-ai), written in **plain PHP 8.2+**.

It doesn't try to compete with Python.  
It exists to remind you that **understanding beats horsepower**.

This project lets you explore how an AI system actually works — provider calls, tools, embeddings, vector stores, retrieval pipelines, memory, and agents — using a visible PHP orchestration layer.

> **Forthcoming from Apress:** *Building AI Agents with PHP* by Paolo Mulas. This repository is the book’s companion codebase.

---

## 🧠 Why?

Many AI frameworks assume abundant compute and several layers of tooling.
This one assumes **an SD card, a coffee, and curiosity** —
and gives you a clear view of how API calls and local control flow work.

**Datapizza-AI PHP** is API-first by design.  
Instead of hiding remote calls behind black boxes, it exposes how every request, embedding, and retrieval happens step by step.

It is built to:
- demystify AI logic for web developers and hobbyists,  
- prove that PHP can still teach serious computer science,  
- run on low-power hardware (file-based, no DB, no composer),  
- serve as a DIY / educational sandbox for students, makers, and retro-computing fans.

It is not enterprise-grade cloud software. It is a transparent codebase for **local automations, document search, and home-lab AI experiments**.

With native integration for **n8n** and **Model Context Protocol (MCP)**,  
Datapizza-AI PHP acts as a bridge between your local logic and modern AI ecosystems.  
You can orchestrate flows, manage context, and exchange data between models and sensors — all from a Raspberry Pi or any small self-hosted box.

Every cosine distance, every JSON write, every API call is visible and hackable.

---

## 🧩 A deliberately small architecture

**Datapizza-AI PHP** takes a deliberately direct path. It does not try to replace larger frameworks; it keeps the moving parts small enough to inspect, change, and test.

- No Composer, no Docker, no Conda.  
- No hidden daemons or background services.  
- Algorithms written line by line, from scratch.  
- Vector stores as plain JSON, readable with any text editor.  
- Cosine similarity calculated in vanilla PHP — no math libraries required.  
- Designed to run where complexity isn't welcome: your local machine.

Think of it as a **garage workshop**: compact, curious, and transparent. You can open it, break it, fix it, and understand it.

---

## 🧩 Architecture overview
```
datapizza-ai-php/
├── datapizza/        # Agents, clients, embedders, tools, memory, and pipelines
├── examples/         # Guided examples and offline verification scripts
├── demos/            # Small runnable demonstrations
├── data/             # Local example data
└── README.md         # Project orientation
```

Each folder is self-contained, readable, and ready to hack.

---

## ⚙️ Requirements

- PHP ≥ 8.2, with `curl` and `json` enabled
- 256 MB RAM is sufficient for the local PHP side of the examples
- Internet access and valid credentials are required for provider-backed calls

Works on:
- Raspberry Pi Model B (2011) — launched at **$35**, sipping around **3 watts** of power  
- Zero W / 3B / 4  
- Any shared hosting or XAMPP/LAMP stack  

> "Runs happily on boards that cost less than your monthly coffee habit."

---

## 🚀 Quick start
```bash
git clone https://github.com/paolomulas/datapizza-ai-php.git
cd datapizza-ai-php
php -S localhost:8080 -t examples
php examples/01_getting_started/hello_pizza.php
```
Expected output:
```
🍕 Hello from Datapizza-AI PHP — running fine on pure curiosity!
```

---

## 🧮 How it works

1. **Embeddings** – text → vector (`text-embedding-3-small`), saved in `/data/vectors.json`.  
2. **Vector Store** – file-based cosine search in PHP; no SQL, no FAISS, just math.  
3. **RAG Pipeline** – `ingestion_pipeline.php` indexes, `dag_pipeline.php` retrieves context.  
4. **Agents + Tools** – `ReactAgent` reasons and calls `calculator`, `wikipedia_search`, `duckduckgo_search`.  
5. **Memory** – `conversation_memory.php` keeps a lightweight dialogue state.  

---

## 🧪 Demo scripts

| Example | Purpose |
|----------|----------|
| `examples/01_getting_started/hello_pizza.php` | First provider-backed PHP call |
| `examples/02_agents/agent.php` | A small tool-using agent |
| `examples/02_agents/agent_with_memory.php` | Conversation memory |
| `examples/03_rag/rag_chatbot.php` | Retrieval-grounded interaction |
| `examples/04_advanced/dag_pipeline.php` | Composed retrieval pipeline |
| `examples/04_advanced/tests/` | Offline checks for tools, retrieval, memory, and operational boundaries |
| `examples/05_sysadmin/` | Bounded disk, uptime, and literal log inspection tools |
| `examples/06_loop_engineering/` | Trace, evidence, and delegated-review examples |

---

## 🔬 Under the hood

Everything lives in plain JSON.  
Similarity is computed transparently:

```php
$similarity = $dot / (sqrt($na) * sqrt($nb));
```

No vector databases.  
No hidden optimizations.  
Just logic and loops.

That's it — no NumPy, no BLAS, no GPU acceleration.  
Just math, curiosity, and a board that sips about **3 watts** of power.

---

## 🧩 Design principles

- **Zero dependencies** — everything hand-coded in PHP  
- **API-first** — callable via HTTP or CLI  
- **Readable > Optimal** — clarity beats performance  
- **Runs anywhere** — shared hosts, Raspberry Pi, old netbooks  
- **Transparent** — trace every step, understand every result  

This is not about horsepower — it's about **comprehension**.

---

## 🔌 For DIY, Makers & Local Hosting

Despite its educational DNA, **Datapizza-AI PHP** can support useful experiments.
Run it on a Raspberry Pi or an old laptop as a **local AI sandbox** for:

- indexing and querying personal notes or PDF docs,  
- powering a voice or chat assistant for your home automation,  
- experimenting with sensors, APIs, and reasoning tasks,  
- building fully private prototypes that never leave your LAN.

No cloud lock-in. No telemetry.  
Just your data, your machine, and a few hundred lines of PHP.

---

## 🧑‍🏫 Educational example
```php
require_once 'pipeline/ingestion_pipeline.php';
require_once 'agents/react_agent.php';

$agent = new ReactAgent(['calculator','wikipedia_search']);
echo $agent->run("Who invented the microprocessor?");
```
Pipeline:
1. Create embedding  
2. Store vectors into the local vector store (`/data/vectors.json`)  
3. Retrieve context  
4. Prompt LLM  
5. Print answer  

That's RAG — no frameworks, no cloud, no mystery.

---

## ⚠️ Known limits
- Provider-backed calls depend on network access, credentials, and provider response shapes
- The JSON vector store is educational: it uses simple file I/O, memory loading, and linear search
- File I/O uses simple locks and execution is single-threaded
- Tool schemas help guide model output; they are not a security boundary by themselves
- The repository is intended for learning and bounded experimentation, not production deployment without further hardening

---

## 💡 Future ideas
- Optional SQLite backend  
- Tiny web UI for debugging  
- Extra tools (YouTube, PDF)  
- SHA-1 embedding cache  
- *"AI on Raspberry"* tutorial series  

---

## 📰 Featured In

**ADMIN Magazine, Issue 92 – February 2026**  
*"Datapizza-AI-PHP: Edge AI Automation on a 2011 Raspberry Pi"*

This project was featured in ADMIN Magazine through the **Sysadmin Agent** use case.
The companion example uses bounded, read-only disk-space, uptime, and literal log-search tools. Provider-backed agent runs require credentials and an operator-selected, non-sensitive log file.

👉 See the complete implementation in `examples/05_sysadmin/`

---

## 📜 License

MIT License © 2025  
Built by **Paolo Mulas** (paolomulas)

If this project or its architectural ideas are reused,
a reference to this repository is appreciated.


---

## ❤️ Credits
Inspired by [Datapizza Labs](https://github.com/datapizza-labs/datapizza-ai)  
This PHP port brings RAG and AI agents to the PHP ecosystem, 
running on vintage Raspberry Pi hardware.
