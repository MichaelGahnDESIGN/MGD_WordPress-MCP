# Builder und Agent Skills

MGD WordPress MCP trennt bewusst zwischen **Werkzeugen** und **Wissen**.

Das WordPress-Plugin stellt MCP-Werkzeuge bereit. Ein Agent Skill kann zusätzlich festlegen, wie ein bestimmter Builder professionell verwendet werden soll.

## Divi 5

Für Divi existiert bereits der öffentliche MGD Divi 5 Dev Skill:

https://github.com/MichaelGahnDESIGN/MGD_Divi5-Dev_SKILL

Der Skill behandelt unter anderem Divi-Workflows, Child Themes, Custom Modules, Design-Systeme, Theme Builder, Backups, Deployment und sichere Builder-Arbeit.

Empfohlene Kombination:

```text
MGD Divi 5 Dev Skill
        ↓
Claude Code / Codex
        ↓
MGD WordPress MCP
        ↓
WordPress + Divi 5
```

Der Skill erklärt dem Agenten, wie er arbeiten soll. Der MCP führt die tatsächlich erlaubten WordPress-Aktionen aus.

## Elementor

Elementor wird vom Einrichtungs-Assistenten erkannt. Version 0.2.0 enthält noch keinen dedizierten Elementor-Strukturadapter. Normale WordPress-Inhalte, Medien, SEO und Wartungswerkzeuge bleiben trotzdem nutzbar.

Ein zukünftiger Elementor-Adapter soll nur dokumentierte und stabile Elementor-Schnittstellen verwenden und keine internen Datenstrukturen auf Verdacht verändern.

## Gutenberg

Gutenberg bzw. der WordPress Block Editor ist der Standard-Fallback. Da Block-Inhalte im WordPress-Content gespeichert werden, können allgemeine Content-Werkzeuge bereits viele Aufgaben abdecken. Ein späterer Block-spezifischer Adapter kann strukturierte Blockoperationen ergänzen.

## Bricks und Beaver Builder

Beide Systeme können als Builder-Kontext erkannt bzw. ausgewählt werden. Spezifische Strukturwerkzeuge sind für spätere Versionen vorgesehen.

## Andere Systeme

Der Assistent erlaubt einen frei benennbaren Builder. Dadurch kann MGD WordPress MCP auch auf Websites eingesetzt werden, die beispielsweise einen individuellen Theme Builder verwenden.

## Warum Skills nicht automatisch vom WordPress-Server installieren?

Claude Code und Codex laufen typischerweise auf einem anderen Rechner als WordPress. Ein WordPress-Plugin sollte nicht versuchen, ohne kontrollierten Client-Kanal Dateien auf diesen Rechner zu installieren.

Deshalb kann der Assistent einen Skill empfehlen und die offizielle GitHub-Quelle öffnen. Die Installation erfolgt anschließend im jeweiligen Agenten-Client.
