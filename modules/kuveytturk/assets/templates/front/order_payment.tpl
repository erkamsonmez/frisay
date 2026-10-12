<div class="payment-option mb-2">
	<label class="d-flex gap-2 align-items-start border rounded p-3 w-100">
		<input type="radio" name="payment_method" id="pay_kuveytturk" value="kuveytturk"{if ($formData.payment_method|default:$selectedPaymentMethod) == 'kuveytturk'} checked{/if}>
		<span>
			<strong>Kredi / Banka Kartı (KuveytTürk 3D Secure)</strong>
			<small class="d-block text-muted">KuveytTürk 3D Secure altyapısı ile tüm banka ve kredi kartlarınızla güvenle ödeme yapabilirsiniz.</small>
		</span>
	</label>
</div>
