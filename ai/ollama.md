## Ollama

- `ollama list` .. list all pulled models
- `ollama ps` .. see the models that is currently running
  - more gpu percentage means quicker the model as it will be served more with the gpu rather than cpu but this requires more gpu vram

#### OCR Model

- `ollama pull glm-ocr`
- `ollama run glm-ocr`
- `ollama run glm-ocr Text Recognition: ./image.png`
- 'http://localhost:11434/api/generate'

```json
{
  "model": "glm-ocr",
  "prompt": "Table Recognition",
  "images": ["PASTE_BASE64_IMAGE_HERE"],
  "stream": false
}
```

note: glm-ocr model only works with the images, so if you have pdf you need to convert it to image first
