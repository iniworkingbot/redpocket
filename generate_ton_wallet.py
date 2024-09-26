from tonsdk.contract.wallet import Wallets, WalletVersionEnum

mnemonics, pub_k, priv_k, wallet = Wallets.create(WalletVersionEnum.v4r2, workchain=0)
wallet_address = wallet.address.to_string(True, True, False)
        
output = wallet_address + "\t" + ' '.join(mnemonics)
print(output)