"""
iot_simulator.py

Simula leituras IoT e envia POST JSON para o endpoint do seu Controller PHP:
    http://localhost:8080/index.php/iot/receive

Funcionalidades:
- Gera leituras plausíveis de temperatura, umidade, oxigênio, CO2 e energia.
- Usa automaticamente 'requests' se instalado; caso contrário, utiliza urllib (nativa).
- Pode rodar continuamente (loop) ou apenas uma vez (--once).
- Permite alterar: INTERVAL, ENDPOINT e DEVICE_ID via argumentos CLI.

Como usar:
    python iot_simulator.py             -> envia dados continuamente
    python iot_simulator.py --once      -> envia apenas 1 leitura
"""

import time        # controla tempo de espera entre envios
import json        # conversão de dicionário para JSON
import random      # gera números aleatórios para simular sensores
import argparse    # permite enviar parâmetros via terminal
from datetime import datetime  # gera timestamps no formato ISO-8601 UTC


# ============================================================
# CONFIGURAÇÕES INICIAIS
# ============================================================

# Endpoint PHP que receberá o JSON (método receiveIoT)
ENDPOINT = "http://localhost:8080/index.php/iot/receive"

# Intervalo padrão entre envios (em segundos)
INTERVAL = 5

# ID do dispositivo simulador
DEVICE_ID = "simulator-001"


# ============================================================
# DETECTA AUTOMATICAMENTE QUAL BIBLIOTECA HTTP UTILIZAR
# ============================================================

_use_requests = False
try:
    import requests   # tenta usar a biblioteca requests
    _use_requests = True
except Exception:
    # se não existir, usa urllib que vem com o Python
    import urllib.request
    import urllib.error


# ============================================================
# GERAÇÃO DO PAYLOAD SIMULADO DE SENSORES
# ============================================================

def simulate_readings():
    """Gera um payload JSON contendo leituras simuladas de sensores IoT."""

    # Temperatura ambiente entre ~20 e 28°C com ruído adicional
    temperature = round(random.uniform(20.0, 28.0) + random.uniform(-0.4, 0.4), 1)

    # Umidade relativa entre 30% e 70%
    humidity = int(round(random.uniform(30.0, 70.0) + random.uniform(-1, 1)))

    # Oxigênio (percentual) entre 88% e 99%
    oxygen = int(round(random.uniform(88.0, 99.0)))

    # CO2 simulado com distribuição gaussiana (valores mais realistas)
    co2 = int(max(350, random.gauss(800, 350)))

    # Energia elétrica: instantânea, total acumulada e pico
    energy_instant = round(random.uniform(0.2, 3.5), 2)
    energy_total   = round(random.uniform(0.5, 10.0), 2)
    energy_peak    = round(max(energy_instant, random.uniform(1.0, 3.8)), 2)

    # Monta o JSON final enviado ao servidor PHP
    payload = {
        "device_id": DEVICE_ID,
        "timestamp": datetime.utcnow().isoformat() + "Z",
        "temperature": temperature,
        "humidity": humidity,
        "oxygen": oxygen,
        "co2": co2,
        "energy": {
            "instant": energy_instant,
            "total": energy_total,
            "peak": energy_peak
        }
    }

    return payload


# ============================================================
# ENVIO DO PAYLOAD UTILIZANDO REQUESTS
# ============================================================

def post_with_requests(payload):
    headers = {"Content-Type": "application/json"}
    r = requests.post(ENDPOINT, json=payload, headers=headers, timeout=6)
    r.raise_for_status()  # gera erro se status não for 200
    return r.status_code, r.text


# ============================================================
# ENVIO DO PAYLOAD UTILIZANDO URLLIB (caso requests não exista)
# ============================================================

def post_with_urllib(payload):
    data = json.dumps(payload).encode("utf-8")
    req = urllib.request.Request(
        ENDPOINT,
        data=data,
        headers={"Content-Type": "application/json"}
    )
    with urllib.request.urlopen(req, timeout=6) as resp:
        status = resp.getcode()
        body = resp.read().decode("utf-8", errors="replace")
    return status, body


# ============================================================
# FUNÇÃO QUE DECIDE AUTOMATICAMENTE COMO ENVIAR O PAYLOAD
# ============================================================

def send_payload(payload):
    try:
        if _use_requests:
            status, body = post_with_requests(payload)
        else:
            status, body = post_with_urllib(payload)

        print(f"[{payload['timestamp']}] Enviado -> status {status} | payload: {json.dumps(payload)}")

        # opcional:
        # print("Resposta do servidor:", body)

    except Exception as e:
        print(f"[{payload.get('timestamp')}] Erro ao enviar: {e}")


# ============================================================
# LOOP PRINCIPAL DO SIMULADOR
# ============================================================

def main(loop=True, interval=INTERVAL):
    print("Iniciando simulador IoT...")
    print("Endpoint alvo:", ENDPOINT)
    print("Usando requests?", _use_requests)

    try:
        while True:
            payload = simulate_readings()  # gera dados simulados
            send_payload(payload)          # envia ao servidor PHP

            if not loop:  # modo --once
                break

            time.sleep(interval)

    except KeyboardInterrupt:
        print("Simulador interrompido pelo usuário.")


# ============================================================
# PARÂMETROS DE LINHA DE COMANDO (CLI)
# ============================================================

if __name__ == "__main__":
    parser = argparse.ArgumentParser(
        description="Simulador IoT - envia JSON para o Controller PHP"
    )

    # Enviar apenas uma vez
    parser.add_argument("--once", action="store_true",
                        help="Enviar apenas uma vez e sair")

    # Ajustar intervalo
    parser.add_argument("--interval", type=float, default=INTERVAL,
                        help="Intervalo entre envios (padrão 5s)")

    # Ajustar endpoint
    parser.add_argument("--endpoint", type=str, default=ENDPOINT,
                        help="URL do endpoint PHP")

    # Ajustar device_id enviado no JSON
    parser.add_argument("--device", type=str, default=DEVICE_ID,
                        help="device_id a incluir no payload")

    args = parser.parse_args()

    # Sobrescreve configurações com valores da CLI
    if args.endpoint:
        ENDPOINT = args.endpoint

    if args.device:
        DEVICE_ID = args.device

    # Executa o simulador
    main(loop=not args.once, interval=args.interval)
