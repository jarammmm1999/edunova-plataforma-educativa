import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output, forwardRef } from '@angular/core';
import { ControlValueAccessor, FormsModule, NG_VALUE_ACCESSOR } from '@angular/forms';


@Component({
  selector: 'app-inputs',
  imports: [CommonModule, FormsModule],
  templateUrl: './inputs.html',
  styleUrl: './inputs.css',
  standalone: true,
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => InputsWidget),
      multi: true,
    },
  ],
})
export class InputsWidget implements ControlValueAccessor {
  @Input() label = '';
  @Input() type = 'text';
  @Input() placeholder = '';
  @Input() name = '';
  @Input() required = false;
  @Input() ngModel: any;
  @Output() ngModelChange = new EventEmitter<any>();

  value: string = '';
  verContrasena = false;
  fueTocado = false;
  campoInvalido = false;

  onChange = (_: any) => {};
  onTouched = () => {};

  writeValue(value: any): void {
    this.value = value;
  }

  registerOnChange(fn: any): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: any): void {
    this.onTouched = fn;
  }

  actualizarValor(event: any): void {
    this.value = event.target.value;
    this.onChange(this.value);
    this.validarCampo();
  }

  onInputBlur(): void {
    this.fueTocado = true;
    this.onTouched();
    this.validarCampo();
  }

  validarCampo(): void {
    this.campoInvalido =
      this.required && (!this.value || this.value.trim() === '');
  }

  toggleVerContrasena(): void {
    this.verContrasena = !this.verContrasena;
  }
}
