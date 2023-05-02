jQuery(document).ready(function($){
    const svgs = document.querySelector('.rating').children;
    for(let i = 0;i<svgs.length;i++){ 
        svgs[i].onclick = ()=>{
            for(let j = 0;j<=i;j++){
                svgs[j].classList.add("fill-yellow-200"); // this class should be added to whitelist while in production mode
            }
            for(let k = i + 1;k<svgs.length;k++){
                svgs[k].classList.remove("fill-yellow-200"); // this class should be added to whitelist while in production mode
            }
        }
    }
});