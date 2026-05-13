/**
 * VAXX · Orçamento page UX
 * - Toggle PF/PJ
 * - Masks CEP/CPF/CNPJ/telefone (vanilla, leves)
 * - ViaCEP autofill
 * - Submit loading state
 */
(function () {
	'use strict';

	const form = document.getElementById('vxOrcForm');
	if (!form) return;

	// ─── PF/PJ toggle ────────────────────────────────────
	const pfBlock = form.querySelector('[data-pf]');
	const pjBlock = form.querySelector('[data-pj]');
	const radios = form.querySelectorAll('input[name="tipo_pessoa"]');

	function applyTipo() {
		const tipo = form.querySelector('input[name="tipo_pessoa"]:checked')?.value || 'pf';
		const isPj = tipo === 'pj';
		if (pfBlock) {
			pfBlock.hidden = isPj;
			pfBlock.querySelectorAll('input').forEach(i => {
				if (i.name === 'nome') i.required = !isPj;
				if (i.name === 'cpf') i.required = !isPj;
			});
		}
		if (pjBlock) {
			pjBlock.hidden = !isPj;
			pjBlock.querySelectorAll('input').forEach(i => {
				if (['razao_social','cnpj','responsavel'].includes(i.name)) i.required = isPj;
			});
		}
	}
	radios.forEach(r => r.addEventListener('change', applyTipo));
	applyTipo();

	// ─── Masks ───────────────────────────────────────────
	const masks = {
		cep: v => v.replace(/\D/g, '').slice(0, 8).replace(/(\d{5})(\d)/, '$1-$2'),
		cpf: v => v.replace(/\D/g, '').slice(0, 11)
			.replace(/(\d{3})(\d)/, '$1.$2')
			.replace(/(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
			.replace(/\.(\d{3})(\d{1,2})$/, '.$1-$2'),
		cnpj: v => v.replace(/\D/g, '').slice(0, 14)
			.replace(/^(\d{2})(\d)/, '$1.$2')
			.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
			.replace(/\.(\d{3})(\d)/, '.$1/$2')
			.replace(/(\d{4})(\d)/, '$1-$2'),
		phone: v => {
			const n = v.replace(/\D/g, '').slice(0, 11);
			if (n.length <= 2) return n;
			if (n.length <= 6) return `(${n.slice(0, 2)}) ${n.slice(2)}`;
			if (n.length <= 10) return `(${n.slice(0, 2)}) ${n.slice(2, 6)}-${n.slice(6)}`;
			return `(${n.slice(0, 2)}) ${n.slice(2, 7)}-${n.slice(7)}`;
		},
	};
	form.querySelectorAll('[data-mask]').forEach(inp => {
		const fn = masks[inp.dataset.mask];
		if (!fn) return;
		// Aplica máscara em valor inicial (se vier pré-preenchido sem máscara)
		if (inp.value) inp.value = fn(inp.value);
		inp.addEventListener('input', () => {
			const start = inp.selectionStart;
			const before = inp.value;
			inp.value = fn(inp.value);
			// Mantém cursor próximo
			if (start !== null && before.length === inp.value.length) {
				inp.setSelectionRange(start, start);
			}
		});
	});

	// ─── ViaCEP autofill ─────────────────────────────────
	const cep = document.getElementById('vxOrcCep');
	const hint = document.getElementById('vxOrcCepHint');
	const rua = document.getElementById('vxOrcRua');
	const bairro = document.getElementById('vxOrcBairro');
	const cidade = document.getElementById('vxOrcCidade');
	const uf = document.getElementById('vxOrcUf');
	const numero = document.getElementById('vxOrcNum');

	async function lookupCep() {
		const raw = (cep?.value || '').replace(/\D/g, '');
		if (raw.length !== 8) return;
		if (hint) { hint.textContent = 'Buscando endereço…'; hint.className = 'vx-orc__hint vx-orc__hint--loading'; }
		try {
			const res = await fetch(`https://viacep.com.br/ws/${raw}/json/`, { mode: 'cors' });
			const data = await res.json();
			if (data.erro) {
				if (hint) { hint.textContent = 'CEP não encontrado'; hint.className = 'vx-orc__hint vx-orc__hint--error'; }
				return;
			}
			if (rua && data.logradouro) rua.value = data.logradouro;
			if (bairro && data.bairro) bairro.value = data.bairro;
			if (cidade && data.localidade) cidade.value = data.localidade;
			if (uf && data.uf) uf.value = data.uf;
			if (hint) { hint.textContent = 'Endereço preenchido. Confira e ajuste o número.'; hint.className = 'vx-orc__hint'; }
			// Foco no próximo campo lógico (número)
			if (numero) numero.focus();
		} catch (e) {
			if (hint) { hint.textContent = 'Falha ao buscar CEP. Preencha manualmente.'; hint.className = 'vx-orc__hint vx-orc__hint--error'; }
		}
	}
	cep?.addEventListener('blur', lookupCep);
	cep?.addEventListener('keydown', e => {
		if (e.key === 'Enter') { e.preventDefault(); lookupCep(); }
	});

	// UF: sempre uppercase + 2 chars
	uf?.addEventListener('input', () => {
		uf.value = uf.value.replace(/[^A-Za-z]/g, '').slice(0, 2).toUpperCase();
	});

	// ─── Submit loading state ───────────────────────────
	const submitBtn = document.getElementById('vxOrcSubmit');
	const submitLabel = submitBtn?.querySelector('.vx-orc__submit-label');
	form.addEventListener('submit', () => {
		if (submitBtn) {
			submitBtn.disabled = true;
			if (submitLabel) submitLabel.textContent = 'Enviando…';
		}
	});
})();
