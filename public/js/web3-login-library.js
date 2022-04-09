
(function($){

    var web3LoginButton = $('#web3loginConnect');

    if (! (web3LoginButton.length > 0) ) return;

    var web3LoginButtonOrgValue = web3LoginButton.html();
    const web3 = new Web3(window.ethereum);
    var globalAccount;
    var globalSignature;
    var nonce = new Date().getTime();
    var web3Login = async function() {
        // web3 instance for signature recovery

        if (typeof window.ethereum !== 'undefined') {
            web3LoginButton.disabled = true;
            web3LoginButton.html('Connecting...');
            await checkNetwork();
            await getAccount();
        }
    }
    var getAccount = async function() {
        const accounts = await window.ethereum.request({
            method: 'eth_requestAccounts'
        });
        const account = accounts[0];
        globalAccount = accounts[0];
        await signMessage();
        await verifyMessage();
        
    }

    var checkNetwork = async function() {
        const chainId = 1;
        if (window.ethereum.networkVersion !== chainId) {
            try {
                await window.ethereum.request({
                    method: 'wallet_switchEthereumChain',
                    params: [{ chainId: web3.utils.toHex(chainId) }],
                });
            } catch (err) {
                // This error code indicates that the chain has not been added to MetaMask.
                if (err.code === 4902) {
                    if (137 == chainId) { 
                        await window.ethereum.request({
                            method: 'wallet_addEthereumChain',
                            params: [
                                {
                                    chainName: 'Polygon Mainnet',
                                    chainId: web3.utils.toHex(chainId),
                                    nativeCurrency: { name: 'MATIC', decimals: 18, symbol: 'MATIC' },
                                    rpcUrls: ['https://polygon-rpc.com/'],
                                },
                            ],
                        });
                    }
                }
            }
        }
    }
    async function signMessage() {
        const message = 'Allow web3-login at ' + nonce;
        try {
            const from = globalAccount;
            const msg = `0x${bops.from(message, 'utf8').toString('hex')}`;
            const sign = await ethereum.request({
                method: 'personal_sign',
                params: [message, from],
            });
            globalSignature = sign;
            verifyMessage();
        } catch (err) {
            console.error(err);
            resetForm();
        }
    }


    async function verifyMessage() {
        const message = 'Allow web3-login at ' + nonce;
        try {
            const from = globalAccount;
            const msg = `0x${bops.from(message, 'utf8').toString('hex')}`;
            const recoveredAddr = web3.eth.accounts.recover(message, globalSignature);
            if (recoveredAddr.toLowerCase() === from.toLowerCase()) {
                finalize_login();
            } else {
                signMessage();
            }
        } catch (err) {
            console.error(err);
            resetForm();
        }
    }
    web3LoginButton.on('click', function () {
        try{
            web3Login();
        } catch (err) {
            console.error(err);
            resetForm();
        }
    });

    var finalize_login = function() {
        window.location.href = '/wp-admin/admin-ajax.php?action=web3_login_authenticate&address=' + globalAccount + '&nonce=' + nonce + '&sig=' + globalSignature;
    }

    var resetForm = function() {
        web3LoginButton.html(web3LoginButtonOrgValue);
        web3LoginButton.disabled = false;
    }
})(jQuery);