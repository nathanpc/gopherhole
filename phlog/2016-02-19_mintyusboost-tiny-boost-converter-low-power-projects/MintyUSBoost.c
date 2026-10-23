/*
 * File:   MintyUSBoost.c
 * Author: Nathan Campos <nathanpc@dreamintech.net>
 *
 * Created on February 14, 2016, 2:45 PM
 */

//                        12F683
//                      +---------+
//                     -|Vdd   Vss|-
// 18MHz XTAL = CLKIN  -|GP5   GP0|- AN0  = Vout Sense
// 18MHz XTAL = CLKOUT -|GP4   GP1|-
//                     -|GP3   GP2|- CCP1 = PWM Output
//                      +---------+

#include <xc.h>
#include <pic12f683.h>
#include <stdint.h>

#pragma config MCLRE = OFF, CP = OFF, CPD = OFF, BOREN = OFF, WDTE = OFF
#pragma config PWRTE = OFF, FOSC = INTOSCIO

#define VREF 3.25
#define VSET 602
#define _XTAL_FREQ 8000000
#define HIGH 1
#define LOW  0

uint8_t sGPIO = 0;

void main(void) {
	OSCCONbits.IRCF = 0b111;  // 8MHz clock frequency.
	OSCCONbits.SCS = 1;  // Internal clock.

	// Setup the pins.
	TRISIO = 0b110001;
	ANSEL = 0;
	GPIO = sGPIO;

	// Setup ADC.
	ANSELbits.ADCS = 0b001;  // Fosc / 8 = 2.0us (4MHz clock).
	ANSELbits.ANS = 0b0001;  // Enable AN0.
	ADCON0bits.ADFM = 1;     // LSB of result in ADRESH<7>
	ADCON0bits.VCFG = 0;     // Voltage reference set to Vdd.
	ADCON0bits.CHS = 0b00;   // Select channel AN0.
	ADCON0bits.ADON = 1;     // Turn ADC ON.

	// Initialize the PWM module.
	T2CONbits.T2CKPS = 0b00;     // Timer2 Prescaler = 1.
	T2CONbits.TMR2ON = 1;        // Enable Timer2.
	PR2 = 0x19;                  // Period = 76.92kHz from datasheet.
	CCP1CONbits.CCP1M = 0b1100;  // Single output active high PWM.

	CCP1CONbits.DC1B  = 0;
	CCPR1L = 0;

	uint8_t pwm = 0;

	// ADC value.
	unsigned int res = 0;

	while (1) {
		__delay_us(10);  // Acquisition delay.
		ADCON0bits.GO = 1;
		while (ADCON0bits.nDONE)
			;
		res = (ADRESH << 8) | ADRESL;

		if (res < VSET) {
			if (pwm < 253) {
				pwm++;

				CCP1CONbits.DC1B = pwm & 0b0000000011;
				CCPR1L = pwm >> 2;
			} else {
				pwm = 0;
			}
		} else if (res > VSET) {
			if (pwm > 0) {
				pwm--;

				CCP1CONbits.DC1B = pwm & 0b0000000011;
				CCPR1L = pwm >> 2;
			}
		}
	}
}