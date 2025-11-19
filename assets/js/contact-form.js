(() => {
	const ajaxUrl = window.avaContactForm ? window.avaContactForm.ajaxUrl : '';

	if (!ajaxUrl) {
		return;
	}

	const i18n = window.avaContactForm && window.avaContactForm.i18n ? window.avaContactForm.i18n : {};
	const requiredMessage = i18n.required || 'Veuillez remplir tous les champs obligatoires.';
	const sendingMessage = i18n.sending || 'Envoi en cours ...';
	const successFallback = 'Merci pour votre message ! Nous revenons vers vous tres vite.';
	const errorFallback = 'Une erreur est survenue. Merci de reessayer.';

	const showMessage = (wrapper, type, text) => {
		const feedback = wrapper.querySelector('.ava-contact-form__feedback');

		if (!feedback) {
			return;
		}

		feedback.textContent = text || '';
		feedback.classList.remove('is-success', 'is-error', 'is-pending');
		if (type) {
			feedback.classList.add(type);
			wrapper.classList.add('ava-contact-form--has-feedback');
		} else {
			wrapper.classList.remove('ava-contact-form--has-feedback');
		}

		if (type === 'is-success') {
			wrapper.classList.add('ava-contact-form--has-success');
			wrapper.classList.remove('ava-contact-form--has-error');
		} else if (type === 'is-error') {
			wrapper.classList.add('ava-contact-form--has-error');
			wrapper.classList.remove('ava-contact-form--has-success');
		} else {
			wrapper.classList.remove('ava-contact-form--has-success', 'ava-contact-form--has-error');
		}

		if (type === 'is-success' || type === 'is-error') {
			setTimeout(() => {
				if (typeof feedback.scrollIntoView === 'function') {
					feedback.scrollIntoView({ behavior: 'smooth', block: 'center' });
				}
			}, 50);
		}
	};

	const updateOptionState = (input) => {
		if (!input) {
			return;
		}

		const option = input.closest('.ava-contact-form__option');

		if (!option) {
			return;
		}

		if (input.type === 'checkbox') {
			option.classList.toggle('is-selected', input.checked);
			return;
		}

		if (input.type === 'radio') {
			const name = input.name;
			if (name) {
				document
					.querySelectorAll(`input[type="radio"][name="${CSS.escape(name)}"]`)
					.forEach((radio) => {
						const radioOption = radio.closest('.ava-contact-form__option');
						if (radioOption) {
							radioOption.classList.toggle('is-selected', radio.checked);
						}
					});
			} else {
				option.classList.toggle('is-selected', input.checked);
			}
		}
	};

	const bindOptionToggles = (form) => {
		if (!form) {
			return;
		}

		const optionInputs = form.querySelectorAll(
			'.ava-contact-form__option input[type="checkbox"], .ava-contact-form__option input[type="radio"]'
		);

		optionInputs.forEach((input) => {
			updateOptionState(input);
			if (!input.dataset.avaOptionBound) {
				input.dataset.avaOptionBound = '1';
				input.addEventListener('change', () => updateOptionState(input));
			}
		});
	};

	const clearFieldError = (wrapper) => {
		if (!wrapper) {
			return;
		}

		wrapper.classList.remove('has-error');
		wrapper.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
	};

	const markFieldInvalid = (wrapper, elements) => {
		if (!wrapper) {
			return;
		}

		wrapper.classList.add('has-error');
		(elements || wrapper.querySelectorAll('input, textarea, select')).forEach((el) => {
			el.classList.add('is-invalid');
		});
	};

	const validateFieldWrapper = (wrapper) => {
		if (!wrapper) {
			return true;
		}

		const required = wrapper.dataset.required === 'true';

		if (!required) {
			return true;
		}

		const fieldType = wrapper.dataset.fieldType || '';
		let isValid = false;

		clearFieldError(wrapper);

		switch (fieldType) {
			case 'checkbox': {
				const checkboxes = Array.from(wrapper.querySelectorAll('input[type="checkbox"]'));
				isValid = checkboxes.some((checkbox) => checkbox.checked);
				if (!isValid) {
					markFieldInvalid(wrapper, checkboxes);
				}
				break;
			}
			case 'radio': {
				const radios = Array.from(wrapper.querySelectorAll('input[type="radio"]'));
				isValid = radios.some((radio) => radio.checked);
				if (!isValid) {
					markFieldInvalid(wrapper, radios);
				}
				break;
			}
			case 'multiselect': {
				const select = wrapper.querySelector('select');
				isValid = !!select && Array.from(select.selectedOptions).length > 0;
				if (!isValid && select) {
					markFieldInvalid(wrapper, [select]);
				}
				break;
			}
			case 'select': {
				const select = wrapper.querySelector('select');
				isValid = !!select && select.value.trim() !== '';
				if (!isValid && select) {
					markFieldInvalid(wrapper, [select]);
				}
				break;
			}
			default: {
				const control = wrapper.querySelector('input, textarea, select');
				if (!control) {
					return true;
				}
				isValid = control.value.trim() !== '';
				if (!isValid) {
					markFieldInvalid(wrapper, [control]);
				}
				break;
			}
		}

		return isValid;
	};

	const validateForm = (form) => {
		const wrappers = Array.from(form.querySelectorAll('.ava-contact-form__field'));
		let isValid = true;
		let firstInvalidElement = null;

		wrappers.forEach((wrapper) => {
			const wrapperValid = validateFieldWrapper(wrapper);
			if (!wrapperValid) {
				isValid = false;
				if (!firstInvalidElement) {
					firstInvalidElement = wrapper.querySelector('input, textarea, select');
				}
			}
		});

		return { isValid, firstInvalidElement };
	};

	const setupInputCleanup = (form) => {
		const clearHandler = (event) => {
			const target = event.target;
			if (!target || !target.classList) {
				return;
			}
			if (target.type === 'checkbox' || target.type === 'radio') {
				updateOptionState(target);
			}
			const wrapper = target.closest('.ava-contact-form__field');
			if (wrapper) {
				if (target.type === 'checkbox' || target.type === 'radio') {
					if (target.checked) {
						clearFieldError(wrapper);
					}
				} else if (target.value.trim()) {
					clearFieldError(wrapper);
				}
			}
		};

		form.addEventListener('input', clearHandler);
		form.addEventListener('change', clearHandler);
	};

	const setupMultiStep = (wrapper) => {
		const form = wrapper.querySelector('.ava-contact-form__form');

		if (!form) {
			return;
		}

		setupInputCleanup(form);

		const mode = wrapper.dataset.mode || 'single';

		if (mode !== 'multi') {
			bindOptionToggles(form);
			return;
		}

		const steps = Array.from(form.querySelectorAll('.ava-contact-form__step'));

		if (!steps.length) {
			return;
		}

		const indicators = Array.from(wrapper.querySelectorAll('.ava-contact-form__step-indicator'));
		const connectors = Array.from(wrapper.querySelectorAll('.ava-contact-form__progress-line'));
		const stepsWrapper = form.querySelector('.ava-contact-form__steps-wrapper');
		const stepsTrack = form.querySelector('.ava-contact-form__steps-track');
		const prevButton = form.querySelector('.ava-contact-form__prev');
		const nextButton = form.querySelector('.ava-contact-form__next');
		const submitButton = form.querySelector('button[type="submit"]');

		bindOptionToggles(form);

		let currentStep = 0;
		let wrapperHeightRaf = null;
		const scheduleWrapperHeight = () => {
			if (!stepsWrapper) {
				return;
			}

			if (wrapperHeightRaf) {
				cancelAnimationFrame(wrapperHeightRaf);
			}

			wrapperHeightRaf = requestAnimationFrame(() => {
				const activeStep = steps[currentStep];
				if (!activeStep) {
					stepsWrapper.style.height = '';
					return;
				}

				const height = activeStep.offsetHeight;
				stepsWrapper.style.height = `${height}px`;
			});
		};

		const focusFirstField = () => {
			const activeStep = steps[currentStep];
			if (!activeStep) {
				return;
			}
			const focusTarget = activeStep.querySelector('input, textarea, select');
			if (focusTarget && typeof focusTarget.focus === 'function') {
				focusTarget.focus({ preventScroll: true });
			}
		};

		const updateStepState = () => {
			steps.forEach((stepEl, index) => {
				const isActive = index === currentStep;
				stepEl.classList.toggle('is-active', isActive);
				stepEl.setAttribute('aria-hidden', isActive ? 'false' : 'true');
			});

			indicators.forEach((indicator, index) => {
				indicator.classList.toggle('is-active', index === currentStep);
				indicator.classList.toggle('is-complete', index < currentStep);
				indicator.setAttribute('aria-selected', index === currentStep ? 'true' : 'false');

				const item = indicator.closest('.ava-contact-form__progress-item');
				if (item) {
					item.classList.toggle('is-active', index === currentStep);
					item.classList.toggle('is-complete', index < currentStep);
				}
			});

			connectors.forEach((line, index) => {
				line.classList.toggle('is-complete', index < currentStep);
			});

			if (stepsTrack) {
				stepsTrack.style.transform = `translateX(-${currentStep * 100}%)`;
			}

			if (prevButton) {
				prevButton.classList.toggle('is-hidden', currentStep === 0);
			}

			if (nextButton) {
				nextButton.classList.toggle('is-hidden', currentStep >= steps.length - 1);
			}

			if (submitButton) {
				submitButton.classList.toggle('is-hidden', currentStep < steps.length - 1);
			}

			scheduleWrapperHeight();
		};

		const validateCurrentStep = () => {
			const stepEl = steps[currentStep];

			if (!stepEl) {
				return true;
			}

			const wrappers = Array.from(stepEl.querySelectorAll('.ava-contact-form__field'));
			let isValid = true;
			let firstInvalid = null;

			wrappers.forEach((wrapper) => {
				if (!validateFieldWrapper(wrapper)) {
					isValid = false;
					if (!firstInvalid) {
						firstInvalid = wrapper.querySelector('input, textarea, select');
					}
				}
			});

			if (!isValid) {
				showMessage(wrapper, 'is-error', requiredMessage);
				if (firstInvalid && typeof firstInvalid.focus === 'function') {
					firstInvalid.focus({ preventScroll: true });
				}
			} else {
				showMessage(wrapper, '', '');
			}

			return isValid;
		};

		if (nextButton) {
			nextButton.addEventListener('click', (event) => {
				event.preventDefault();
				if (!validateCurrentStep()) {
					return;
				}
				currentStep = Math.min(currentStep + 1, steps.length - 1);
				updateStepState();
				focusFirstField();
			});
		}

		if (prevButton) {
			prevButton.addEventListener('click', (event) => {
				event.preventDefault();
				currentStep = Math.max(currentStep - 1, 0);
				updateStepState();
				showMessage(wrapper, '', '');
				focusFirstField();
			});
		}

		updateStepState();
		focusFirstField();
		bindOptionToggles(form);
		scheduleWrapperHeight();

		if (stepsWrapper) {
			window.addEventListener('resize', scheduleWrapperHeight);
		}

		form._avaCfMultiStep = {
			reset: (options = {}) => {
				currentStep = 0;
				steps.forEach((stepEl) => {
					stepEl.querySelectorAll('.ava-contact-form__field').forEach((wrapper) => clearFieldError(wrapper));
				});
				updateStepState();
				if (!options.keepMessage) {
					showMessage(wrapper, '', '');
				}
				focusFirstField();
				bindOptionToggles(form);
			},
		};
	};

	const handleSubmit = (event) => {
		const form = event.target;

		if (!form.classList.contains('ava-contact-form__form')) {
			return;
		}

		if (event.defaultPrevented) {
			return;
		}

		event.preventDefault();

		const wrapper = form.closest('.ava-contact-form');

		if (!wrapper) {
			return;
		}

		const submitButton = form.querySelector('button[type="submit"]');

		if (submitButton) {
			submitButton.disabled = true;
			submitButton.dataset.originalText = submitButton.dataset.originalText || submitButton.textContent;
			submitButton.classList.add('is-loading');
			submitButton.textContent = sendingMessage;
		}

		showMessage(wrapper, 'is-pending', sendingMessage);

		const { isValid, firstInvalidElement } = validateForm(form);

		if (!isValid) {
			if (submitButton) {
				submitButton.disabled = false;
				submitButton.classList.remove('is-loading');
				if (submitButton.dataset.originalText) {
					submitButton.textContent = submitButton.dataset.originalText;
				}
			}
			showMessage(wrapper, 'is-error', requiredMessage);
			if (firstInvalidElement && typeof firstInvalidElement.focus === 'function') {
				firstInvalidElement.focus({ preventScroll: true });
			}
			return;
		}

		const formData = new FormData(form);
		formData.append('action', 'ava_contact_form_submit');

		fetch(ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		})
			.then(async (response) => {
				const payload = await response.json().catch(() => ({}));

				if (!response.ok || !payload.success) {
					const errorText =
						(payload && payload.data && payload.data.message) ||
						wrapper.dataset.error ||
						errorFallback;
					throw new Error(errorText);
				}

				return payload;
			})
			.then(() => {
				const successText = wrapper.dataset.success || successFallback;
				showMessage(wrapper, 'is-success', successText);
				form.reset();
				if (form._avaCfMultiStep && typeof form._avaCfMultiStep.reset === 'function') {
					form._avaCfMultiStep.reset({ keepMessage: true });
				}

				const modal = wrapper.closest('.elementor-widget');
				if (modal && typeof elementor !== 'undefined') {
					const closeButton = modal.querySelector('[aria-label="Close"], .dialog-close-button');
					if (closeButton) {
						closeButton.click();
					}
				}
			})
			.catch((error) => {
				showMessage(wrapper, 'is-error', error.message);
			})
			.finally(() => {
				if (submitButton) {
					submitButton.disabled = false;
					submitButton.classList.remove('is-loading');
					if (submitButton.dataset.originalText) {
						submitButton.textContent = submitButton.dataset.originalText;
					}
				}
			});
	};

	document.querySelectorAll('.ava-contact-form').forEach((wrapper) => {
		setupMultiStep(wrapper);
		const form = wrapper.querySelector('.ava-contact-form__form');
		if (form && wrapper.dataset.mode !== 'multi') {
			bindOptionToggles(form);
		}
	});

	document.addEventListener('submit', handleSubmit);
})();

