import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root',
})
export class SocketService {
  private socket: WebSocket | null = null;
  private readonly url = 'ws://localhost:8080';
  private reconnectInterval = 5000; // 5 segundos
  private isManuallyClosed = false;

  private messageCallbacks: ((data: any) => void)[] = [];

  constructor() {
    this.conectar();
  }

  private conectar() {
    if (typeof window === 'undefined') return;

    this.socket = new WebSocket(this.url);

    this.socket.onopen = () => {
      console.log('✅ WebSocket conectado');
    };

    this.socket.onmessage = (event) => {
      const mensaje = JSON.parse(event.data);
      this.messageCallbacks.forEach((cb) => cb(mensaje));
    };

    this.socket.onerror = (err) => {
      console.error('❌ WebSocket error:', err);
    };

    this.socket.onclose = () => {
      console.warn('⚠️ WebSocket cerrado, intentando reconectar...');
      if (!this.isManuallyClosed) {
        setTimeout(() => this.conectar(), this.reconnectInterval);
      }
    };
  }

  enviarEvento(data: any) {
    const intentarEnviar = () => {
      if (this.socket?.readyState === WebSocket.OPEN) {
        this.socket.send(JSON.stringify(data));
      } else {
        setTimeout(intentarEnviar, 100); // Reintenta cada 100ms
      }
    };
    intentarEnviar();
  }

  recibirEvento(callback: (data: any) => void) {
    this.messageCallbacks.push(callback);
  }

  cerrarConexion() {
    this.isManuallyClosed = true;
    this.socket?.close();
  }
}
