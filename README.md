# 🍕 Datapizza-AI PHP  
> *Designed and built on a Raspberry Pi Model B (2011). No GPU, no Docker, no excuses.*

[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-7.x-blue.svg)]()
[![Platform](https://img.shields.io/badge/Runs_on-RaspberryPi_1_Model_B-red.svg)]()
[![Architecture](https://img.shields.io/badge/Architecture-API--first-lightgrey.svg)]()
[![Focus](https://img.shields.io/badge/Focus-Educational-yellow.svg)]()
[![Power](https://img.shields.io/badge/Power_Usage-~3W-orange.svg)]()
[![Status](https://img.shields.io/badge/Status-Active-brightgreen.svg)]()

**Datapizza-AI PHP** is an educational, ultra-minimal port of the original [Datapizza AI](https://github.com/datapizza-labs/datapizza-ai), rewritten in **pure PHP 7.x**.

It doesn’t try to compete with Python.  
It exists to remind you that **understanding beats horsepower**.

This project lets you explore how an AI system actually works — embeddings, vector stores, retrieval pipelines, and agents — using the most classic web language of them all.

---

## 🧠 Why?

Most AI frameworks assume a cloud GPU farm.  
This one assumes you have **an SD card, a coffee, and curiosity** —  
and a clear view of how APIs really work.

**Datapizza-AI PHP** is API-first by design.  
Instead of hiding remote calls behind black boxes, it exposes how every request, embedding, and retrieval happens step by step.

It was built to:
- demystify AI logic for web developers and hobbyists,  
- prove that PHP can still teach serious computer science,  
- run on low-power hardware (file-based, no DB, no composer),  
- serve as a DIY / educational sandbox for students, makers, and retro-computing fans.

It’s not enterprise-grade cloud software —  
but it’s perfectly capable of powering **local automations, document search, and home-lab AI experiments**.

With native integration for **n8n** and **Model Context Protocol (MCP)**,  
Datapizza-AI PHP acts as a bridge between your local logic and modern AI ecosystems.  
You can orchestrate flows, manage context, and exchange data between models and sensors — all from a Raspberry Pi or any small self-hosted box.

Every cosine distance, every JSON write, every API call is visible and hackable.

---

## 🧩 How it differs from everything else out there

Most AI frameworks — Python or otherwise — are **monuments to dependency hell**.  
They need Conda, CUDA, Poetry, virtual environments, and a small prayer.  
Even the few PHP ones that exist wrap around massive SDKs and require hardware that would melt a Raspberry Pi.

**Datapizza-AI PHP** takes the opposite path:  
it’s not a layer on top of another layer — it’s the ground floor, built brick by brick.

- No Composer, no Docker, no Conda.  
- No hidden daemons or background services.  
- Algorithms written line by line, from scratch.  
- Vector stores as plain JSON, readable with any text editor.  
- Cosine similarity calculated in vanilla PHP — no math libraries required.  
- Designed to run where complexity isn’t welcome: your local machine.

If Python frameworks are skyscrapers, this one’s the **garage workshop** —  
messy, curious, and transparent. You can open it, break it, fix it, and understand it.

---

## 🧩 Architecture overview
```
datapizza-ai-php/
├── agents/           # Core agents (Base, ReactAgent, AgentWithMemory)
├── clients/          # API clients (OpenAI, Anthropic, DeepSeek, etc.)
├── embedders/        # Text embedding generators
├── integrations/     # Tiny HTTP server + endpoints
├── memory/           # Conversation state manager
├── modules/          # Parsers, retrieval utilities
├── pipeline/         # DAG + ingestion + RAG pipeline
├── tools/            # External tools (Wikipedia, DuckDuckGo, Calculator)
├── utils/            # Helpers (cosine, locks, logs)
├── vectorstores/     # Local JSON-based vector store
└── examples/         # Demos and quick tests
```

Each folder is self-contained, readable, and ready to hack.

---

## ⚙️ Requirements

- PHP ≥ 7.0 (only `curl` and `json`)  
- 256 MB RAM is plenty  
- Internet required only for API calls  

Works on:
- Raspberry Pi Model B (2011) — launched at **$35**, sipping around **3 watts** of power  
- Zero W / 3B / 4  
- Any shared hosting or XAMPP/LAMP stack  

> “Runs happily on boards that cost less than your monthly coffee habit.”

---

## 🚀 Quick start
```bash
git clone https://github.com/yourhandle/datapizza-ai-php.git
cd datapizza-ai-php
php -S localhost:8080 -t examples
php examples/hello_pizza.php
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
| `hello_pizza.php` | Sanity check — if it runs, PHP is alive |
| `demo_rag_chatbot.php` | Full RAG + agent pipeline |
| `test_agent_memory_simple.php` | Conversation memory |
| `test_agent_with_search.php` | External tools |
| `test_embedder.php` | Generate and inspect embeddings |
| `test_dag_pipeline.php` | Visualize the pipeline flow |

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

That’s it — no NumPy, no BLAS, no GPU acceleration.  
Just math, curiosity, and a board that sips about **3 watts** of power.

---

## 🧩 Design principles

- **Zero dependencies** — everything hand-coded in PHP  
- **API-first** — callable via HTTP or CLI  
- **Readable > Optimal** — clarity beats performance  
- **Runs anywhere** — shared hosts, Raspberry Pi, old netbooks  
- **Transparent** — trace every step, understand every result  

This is not about horsepower — it’s about **comprehension**.

---

## 🔌 For DIY, Makers & Local Hosting

Despite its educational DNA, **Datapizza-AI PHP** can actually *do work*.  
Run it on your Raspberry Pi or an old laptop and it becomes a **local AI sandbox** — ideal for:

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

That’s RAG — no frameworks, no cloud, no mystery.

---

## ⚠️ Known limits
- Remote embeddings only  
- File I/O uses simple locks  
- Single-thread execution  
- Educational purpose only  

---

## 💡 Future ideas
- Optional SQLite backend  
- Tiny web UI for debugging  
- Extra tools (YouTube, PDF)  
- SHA-1 embedding cache  
- *“AI on Raspberry”* tutorial series  

---

## 📜 License
MIT License © 2025  
Built by **Paolo [add your GitHub handle]**

---

## ❤️ Credits
Inspired by [Datapizza Labs](https://github.com/datapizza-labs/datapizza-ai)  
Ported to PHP to prove that even a 14-year-old Raspberry can still serve hot AI slices.  
